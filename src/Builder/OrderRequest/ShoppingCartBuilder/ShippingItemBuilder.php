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
     * @param Cart $cart
     * @param array $cartSummary
     * @param string $currencyIsoCode
     *
     * @return ShippingItem[]
     * @throws InvalidArgumentException
     */
    public function build(Cart $cart, array $cartSummary, string $currencyIsoCode): array
    {
        $shippingItem = new ShippingItem();

        $totalShippingTax = $cartSummary['total_shipping'] - $cartSummary['total_shipping_tax_exc'];
        $shippingTaxRate  = $cartSummary['total_shipping'] > 0 ?
            ($totalShippingTax * 100) / ($cartSummary['total_shipping'] - $totalShippingTax) : 0;

        if ($this->currentGatewayCode === TaxHelper::GATEWAY_CODE_BILLINK) {
            $shippingTaxRate = TaxHelper::roundTaxRateForBillink($shippingTaxRate);
        }

        $shippingItem
            ->addName(($cartSummary['carrier']->name ?? $this->module->l('Shipping', self::CLASS_NAME)))
            ->addQuantity(1)
            ->addUnitPrice(
                MoneyHelper::createMoney((float)$cartSummary['total_shipping_tax_exc'], $currencyIsoCode)
            )
            ->addTaxRate($shippingTaxRate);

        return [$shippingItem];
    }
}
