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
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use MultiSafepay\PrestaShop\Helper\TaxHelper;
use MultiSafepay\ValueObject\CartItem;
use MultisafepayOfficial;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class DiscountItemBuilder
 * @package MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder
 */
class DiscountItemBuilder implements ShoppingCartBuilderInterface
{
    public const CLASS_NAME = 'DiscountItemBuilder';

    /**
     * @var MultisafepayOfficial
     */
    private $module;

    /**
     * @var string|null
     */
    private $currentGatewayCode = null;

    /**
     * DiscountItemBuilder constructor.
     *
     * @param MultisafepayOfficial $module
     */
    public function __construct(MultisafepayOfficial $module)
    {
        $this->module = $module;
    }

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
     * Build a discount cart item with tax rate calculated from PrestaShop's values.
     *
     * This method creates a discount item using PrestaShop's discount amounts and calculates
     * the tax rate by comparing the total discount with tax vs without tax.
     *
     * The tax rate is derived using the formula:
     * taxRate = ((totalDiscountWithTax - totalDiscountWithoutTax) / totalDiscountWithoutTax) * 100
     *
     * The calculated tax rate is rounded to 2 decimals for compatibility with payment gateways
     * that expect standard tax rates (e.g., 21.00%, 10.00%, 4.00%).
     *
     * @param Cart $cart
     * @param array $cartSummary
     * @param string $currencyIsoCode
     *
     * @return array|CartItem[] Array containing the discount CartItem, or empty array if no discount
     * @throws InvalidArgumentException
     */
    public function build(Cart $cart, array $cartSummary, string $currencyIsoCode): array
    {
        // Extract discount amounts calculated by PrestaShop
        $totalDiscountTaxExc = (float)($cartSummary['total_discounts_tax_exc'] ?? 0.0);
        $totalDiscountTaxInc = (float)($cartSummary['total_discounts'] ?? 0.0);

        // Only skip if BOTH values are zero/negligible (no discount at all)
        // If only one is zero, it could be a valid discount on tax-exempt products
        if ($totalDiscountTaxExc <= TaxHelper::EPSILON &&
            $totalDiscountTaxInc <= TaxHelper::EPSILON) {
            return [];
        }

        // Protect against division by zero when calculating tax rate
        if ($totalDiscountTaxExc <= TaxHelper::EPSILON) {
            $taxRate = 0.0;
        } else {
            // Calculate the tax amount by subtracting both prices
            $totalDiscountTax = $totalDiscountTaxInc - $totalDiscountTaxExc;
            // Calculate tax rate as a percentage
            $taxRate = ($totalDiscountTax * 100) / $totalDiscountTaxExc;
        }
        $taxRate = round($taxRate, 2);

        // Apply special rounding for Billink payment gateway if needed
        if ($this->currentGatewayCode === TaxHelper::GATEWAY_CODE_BILLINK) {
            $taxRate = TaxHelper::roundTaxRateForBillink($taxRate);
        }

        $cartItem = (new CartItem())
            ->addName($this->module->l('Discount', self::CLASS_NAME))
            ->addQuantity(1)
            ->addMerchantItemId('Discount')
            ->addUnitPrice(MoneyHelper::createMoney(-$totalDiscountTaxExc, $currencyIsoCode))
            ->addTaxRate($taxRate);

        return [$cartItem];
    }
}
