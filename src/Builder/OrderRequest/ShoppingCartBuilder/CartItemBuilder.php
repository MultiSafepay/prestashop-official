<?php
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder;

use Cart;
use Configuration;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use MultiSafepay\PrestaShop\Helper\TaxHelper;
use MultiSafepay\ValueObject\CartItem;
use MultiSafepay\ValueObject\Weight;
use Order;
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class CartItemBuilder
 * @package MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder
 */
class CartItemBuilder implements ShoppingCartBuilderInterface
{
    public const PRESTASHOP_ROUNDING_PRECISION = 10;

    /**
     * @var string|null
     */
    private $currentGatewayCode = null;

    /**
     * Set the current gateway code for this request
     *
     * @param string $gatewayCode
     * @return void
     */
    public function setCurrentGatewayCode(string $gatewayCode): void
    {
        $this->currentGatewayCode = $gatewayCode;
    }

    /**
     * @param Cart $cart
     * @param array $cartSummary
     * @param string $currencyIsoCode
     *
     * @return array|CartItem[]
     * @throws InvalidArgumentException
     */
    public function build(Cart $cart, array $cartSummary, string $currencyIsoCode): array
    {
        /** @var array $products */
        $products = $cart->getProductsWithSeparatedGifts();

        $cartItems = [];
        foreach ($products as $product) {
            $cartItems[] = $this->createCartItemFromProduct(
                $product,
                $currencyIsoCode,
                (int)Configuration::get('PS_ROUND_TYPE'),
                Configuration::get('PS_WEIGHT_UNIT')
            );
        }

        return $cartItems;
    }

    /**
     * @param array $product
     * @param string $currencyIsoCode
     * @param int $orderRoundType
     * @param string $weightUnit
     *
     * @return CartItem
     * @throws InvalidArgumentException
     */
    private function createCartItemFromProduct(
        array $product,
        string $currencyIsoCode,
        int $orderRoundType,
        string $weightUnit
    ): CartItem {
        $merchantItemId = (string)$product['id_product'];
        $productName    = $product['name'];
        if (!empty($product['attributes_small'])) {
            $productName .= ' ( '.$product['attributes_small'].' )';
            $merchantItemId .= '-'.$product['id_product_attribute'];
        }

        /**
         * We add '-gift' to the merchantItemId to prevent issues when someone has
         * two or more of the same item, but one of them is a gift
         */
        if ($this->productIsGift($product)) {
            $merchantItemId .= '-gift';
        }

        return $this->createCartItem(
            $productName,
            (int)$product['quantity'],
            $merchantItemId,
            $this->calculatePriceForProduct($product, $orderRoundType),
            $currencyIsoCode,
            $this->calculateProductTaxRate($product),
            new Weight($weightUnit, (float)$product['weight'])
        );
    }

    /**
     * Calculate the tax rate for a product, ensuring consistency with PrestaShop's calculations.
     *
     * Uses the same epsilon comparison approach as calculatePriceForProduct() to safely
     * detect when a product has no taxes applied, avoiding floating point precision issues.
     *
     * Prefers total_wt over price_wt to handle group discounts and specific prices correctly,
     * matching the same data source used for price calculations.
     *
     * @param array $product Product data array from Cart::getProductsWithSeparatedGifts()
     * @return float Tax rate as percentage
     */
    private function calculateProductTaxRate(array $product): float
    {
        // Use the exact price calculated by PrestaShop; otherwise, fallback to our previous way
        $priceWithTax = !empty($product['total_wt']) && (float)$product['quantity'] > 0.0
            ? (float)$product['total_wt'] / (float)$product['quantity']
            : (float)($product['price_wt'] ?: $product['price_with_reduction']);

        $priceWithoutTax = (float)$product['price'];

        // Use epsilon comparison for float precision safety
        // If prices are equal (within epsilon), there are no taxes
        if (abs($priceWithTax - $priceWithoutTax) < TaxHelper::EPSILON) {
            return 0.0;
        }

        $taxRate = round($product['rate'], 2);

        // Apply special rounding for Billink payment gateway if needed
        if ($this->currentGatewayCode === TaxHelper::GATEWAY_CODE_BILLINK) {
            $taxRate = TaxHelper::roundTaxRateForBillink($taxRate);
        }

        return $taxRate;
    }

    /**
     * Calculate the price for a product without taxes, ensuring consistency with PrestaShop's calculations.
     *
     * PrestaShop applies users group discounts at the cart level, not at the individual product level.
     * The 'total_wt' field contains the final price already calculated by PrestaShop with all discounts applied.
     *
     * By dividing 'total_wt' by 'quantity', we get the exact unit price that PrestaShop is using.
     * This ensures we use exactly the same figure as PrestaShop, eliminating cent differences.
     *
     * Handles all rounding configurations: PS_ROUND_TYPE and PS_PRICE_ROUND_MODE via Tools::ps_round(),
     * and group discounts, specific prices, cart rules - all included in total_wt
     *
     * @param array $product Product data array from Cart::getProductsWithSeparatedGifts()
     * @param int $orderRoundType PrestaShop's rounding type configuration (PS_ROUND_TYPE)
     *
     * @return float Price without taxes, ready for MultiSafepay API
     */
    private function calculatePriceForProduct(array $product, int $orderRoundType): float
    {
        /**
         * If the product is a gift product, the price should be 0
         */
        if ($this->productIsGift($product)) {
            return 0.0;
        }

        // Use the exact price calculated by PrestaShop; otherwise, fallback to our previous way
        $priceWithTax = !empty($product['total_wt']) && (float)$product['quantity'] > 0.0
            ? (float)$product['total_wt'] / (float)$product['quantity']
            : (float)($product['price_wt'] ?: $product['price_with_reduction']);

        $taxRate = (float)$product['rate'];

        // Case where there are no taxes or the price is already without taxes
        // Using epsilon comparison for float precision safety as comparing floats
        // with === can fail due to floating point precision issues.
        if (abs($taxRate) < TaxHelper::EPSILON || abs($priceWithTax - (float)$product['price']) < TaxHelper::EPSILON) {
            return Tools::ps_round($priceWithTax, self::PRESTASHOP_ROUNDING_PRECISION);
        }

        // Calculate price without taxes using the same method as PrestaShop
        $priceWithoutTax = $priceWithTax / (1 + ($taxRate / 100));

        /**
         * If rounding mode is set to round per item, we have to round the price of each item before
         * adding it to the shopping cart to prevent 1 cent differences.
         */
        if (Order::ROUND_ITEM === $orderRoundType) {
            $priceWithoutTax = Tools::ps_round($priceWithoutTax, self::PRESTASHOP_ROUNDING_PRECISION);
        }

        return $priceWithoutTax;
    }

    /**
     * @param string $name
     * @param int $quantity
     * @param string $merchantItemId
     * @param float $price
     * @param string $currencyCode
     * @param float $taxrate
     * @param Weight|null $weight
     *
     * @return CartItem
     * @throws InvalidArgumentException
     */
    private function createCartItem(
        string $name,
        int $quantity,
        string $merchantItemId,
        float $price,
        string $currencyCode,
        float $taxrate,
        ?Weight $weight = null
    ): CartItem {
        $cartItem = (new CartItem())
            ->addName($name)
            ->addQuantity($quantity)
            ->addMerchantItemId($merchantItemId)
            ->addUnitPrice(
                MoneyHelper::createMoney($price, $currencyCode)
            )
            ->addTaxRate($taxrate);

        if (isset($weight)) {
            $cartItem->addWeight($weight);
        }

        return $cartItem;
    }

    /**
     * @param array $product
     *
     * @return bool
     */
    private function productIsGift(array $product): bool
    {
        return isset($product['is_gift']) && $product['is_gift'] === true;
    }
}
