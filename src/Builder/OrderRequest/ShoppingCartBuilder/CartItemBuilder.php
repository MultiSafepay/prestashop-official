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
use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use MultiSafepay\PrestaShop\Helper\TaxHelper;
use MultiSafepay\ValueObject\CartItem;
use MultiSafepay\ValueObject\Weight;
use Order;
use Tools;

/**
 * Class CartItemBuilder
 * @package MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder
 */
class CartItemBuilder implements ShoppingCartBuilderInterface
{
    public const PRESTASHOP_ROUNDING_PRECISION = 2;

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
     * @param array $product
     * @return float
     */
    private function calculateProductTaxRate(array $product): float
    {
        // Case in which the product has a product rate set, but the product in the order does not contain taxes
        $priceWithTaxes = $product['price_wt'] ? $product['price_wt'] : $product['price_with_reduction'];
        if ($priceWithTaxes === $product['price']) {
            return 0;
        }

        $taxRate = (float)$product['rate'];

        if ($this->currentGatewayCode === TaxHelper::GATEWAY_CODE_BILLINK) {
            $taxRate = TaxHelper::roundTaxRateForBillink($taxRate);
        }

        return $taxRate;
    }

    /**
     * @param array $product
     * @param int $orderRoundType
     *
     * @return float
     */
    private function calculatePriceForProduct(array $product, int $orderRoundType): float
    {
        /**
         * If the product is a gift product, the price should be 0
         */
        if ($this->productIsGift($product)) {
            return 0;
        }

        $taxRate = (float)$product['rate'];
        $price   = $product['price_wt'] ?: $product['price_with_reduction'];

        // Case in which the product have a product rate set, but the product in the order do not contain taxes
        if ($price === $product['price']) {
            return Tools::ps_round($price, self::PRESTASHOP_ROUNDING_PRECISION);
        }

        /**
         * If rounding mode is set to round per item, we have to round the price of each item before
         * adding it to the shopping cart to prevent 1 cent differences
         */
        if (Order::ROUND_ITEM === $orderRoundType) {
            $price = Tools::ps_round($price, self::PRESTASHOP_ROUNDING_PRECISION);
        }

        return $price * 100 / (100 + $taxRate);
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
        $cartItem = new CartItem();
        $cartItem
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
