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
 * Class WrappingItemBuilder
 * @package MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder
 */
class WrappingItemBuilder implements ShoppingCartBuilderInterface
{
    public const CLASS_NAME = 'WrappingItemBuilder';

    /**
     * @var MultisafepayOfficial
     */
    private $module;

    /**
     * @var string|null
     */
    private $currentGatewayCode = null;

    /**
     * WrappingItemBuilder constructor.
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
     * Build a gift wrapping cart item with tax rate calculated from PrestaShop's values.
     *
     * PrestaShop can apply taxes to gift wrapping based on PS_GIFT_WRAPPING_TAX_RULES_GROUP configuration.
     * The cart summary provides both total_wrapping (with tax) and total_wrapping_tax_exc (without tax).
     *
     * Calculates the tax rate by comparing the wrapping cost with tax vs without tax.
     * Uses epsilon comparison (0.0001) for float comparisons to ensure mathematical precision
     * and avoid floating point precision issues when detecting zero taxes.
     *
     * @param Cart $cart
     * @param array $cartSummary
     * @param string $currencyIsoCode
     *
     * @return array|CartItem[]
     * @throws InvalidArgumentException
     */
    public function build(Cart $cart, array $cartSummary, string $currencyIsoCode): array
    {
        // Extract wrapping amounts calculated by PrestaShop
        $totalWrappingTaxExc = (float)($cartSummary['total_wrapping_tax_exc'] ?? 0.0);
        $totalWrappingTaxInc = (float)($cartSummary['total_wrapping'] ?? 0.0);

        // Only skip if BOTH values are zero/negligible (no wrapping at all)
        // If only one is zero, it could be valid wrapping with 0% VAT
        if ($totalWrappingTaxExc <= TaxHelper::EPSILON &&
            $totalWrappingTaxInc <= TaxHelper::EPSILON) {
            return [];
        }

        // Protect against division by zero when calculating tax rate
        if ($totalWrappingTaxExc <= TaxHelper::EPSILON) {
            $taxRate = 0.0;
        } else {
            // Calculate the tax amount by subtracting both prices
            $totalWrappingTax = $totalWrappingTaxInc - $totalWrappingTaxExc;
            // Calculate tax rate as a percentage
            $taxRate = ($totalWrappingTax * 100) / $totalWrappingTaxExc;
        }
        $taxRate = round($taxRate, 2);

        // Apply special rounding for Billink payment gateway if needed
        if ($this->currentGatewayCode === TaxHelper::GATEWAY_CODE_BILLINK) {
            $taxRate = TaxHelper::roundTaxRateForBillink($taxRate);
        }

        $cartItem = (new CartItem())
            ->addName($this->module->l('Wrapping', self::CLASS_NAME))
            ->addQuantity(1)
            ->addMerchantItemId('Wrapping')
            ->addUnitPrice(
                MoneyHelper::createMoney($totalWrappingTaxExc, $currencyIsoCode)
            )
            ->addTaxRate($taxRate);

        return [$cartItem];
    }
}
