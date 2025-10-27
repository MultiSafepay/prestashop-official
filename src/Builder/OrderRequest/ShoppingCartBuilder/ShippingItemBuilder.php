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
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\ShippingItem;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use MultiSafepay\PrestaShop\Helper\TaxHelper;
use MultisafepayOfficial;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class ShippingItemBuilder
 * @package MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder
 */
class ShippingItemBuilder implements ShoppingCartBuilderInterface
{
    public const CLASS_NAME = 'ShippingItemBuilder';

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
     * Build a shipping tax rate calculated from PrestaShop's values.
     *
     * Calculates the shipping tax rate by comparing total shipping with tax vs without tax.
     * Uses epsilon comparison (0.0001) for float comparisons to ensure mathematical precision.
     *
     * @param Cart $cart
     * @param array $cartSummary
     * @param string $currencyIsoCode
     *
     * @return ShippingItem[]
     * @throws InvalidArgumentException
     */
    public function build(Cart $cart, array $cartSummary, string $currencyIsoCode): array
    {
        // Extract shipping costs from PrestaShop's cart summary
        $totalShippingTaxExc = (float)($cartSummary['total_shipping_tax_exc'] ?? 0.0);
        $totalShippingTaxInc = (float)($cartSummary['total_shipping'] ?? 0.0);

        // Calculate tax rate, protecting against division by zero
        // Note: Free shipping (€0) is valid and should create an item with 0% tax
        if ($totalShippingTaxExc <= TaxHelper::EPSILON) {
            $taxRate = 0.0;
        } else {
            // Calculate the tax amount by subtracting base price from final price
            $totalShippingTax = $totalShippingTaxInc - $totalShippingTaxExc;
            // Calculate tax rate as a percentage
            $taxRate = ($totalShippingTax * 100) / $totalShippingTaxExc;
        }
        $taxRate = round($taxRate, 2);

        // Apply special rounding for Billink payment gateway if needed
        if ($this->currentGatewayCode === TaxHelper::GATEWAY_CODE_BILLINK) {
            $taxRate = TaxHelper::roundTaxRateForBillink($taxRate);
        }

        // Build the shipping item with calculated values
        $shippingItem = new ShippingItem();
        $shippingItem
            ->addName(($cartSummary['carrier']->name ?? $this->module->l('Shipping', self::CLASS_NAME)))
            ->addQuantity(1)
            ->addUnitPrice(
                MoneyHelper::createMoney($totalShippingTaxExc, $currencyIsoCode)
            )
            ->addTaxRate($taxRate);

        return [$shippingItem];
    }
}
