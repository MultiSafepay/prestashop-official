<?php
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
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

namespace MultiSafepay\PrestaShop\Helper;

use MultiSafepay\PrestaShop\Builder\OrderRequestBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\CustomerBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\CustomerBuilder\AddressBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\DeliveryBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\DescriptionBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\GatewayInfoBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\PaymentComponentBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\PaymentOptionsBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\PluginDetailsBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\SecondChanceBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\TimeActiveBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\TokenizationBuilder;
use MultiSafepay\PrestaShop\Util\AddressUtil;
use MultiSafepay\PrestaShop\Util\CustomerUtil;
use MultiSafepay\PrestaShop\Util\CurrencyUtil;
use MultiSafepay\PrestaShop\Util\LanguageUtil;
use MultisafepayOfficial;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class OrderRequestBuilderFallback
 *
 * Helper class to create OrderRequestBuilder with all required dependencies
 * when the Symfony container is not available.
 *
 * @package MultiSafepay\PrestaShop\Helper
 */
class OrderRequestBuilderHelper
{
    /**
     * Create OrderRequestBuilder with all required builders for fallback
     *
     * @param MultisafepayOfficial $module
     * @return OrderRequestBuilder
     */
    public static function create(MultisafepayOfficial $module): OrderRequestBuilder
    {
        // Create utility classes
        $addressBuilder = new AddressBuilder();
        $addressUtil = new AddressUtil();
        $customerUtil = new CustomerUtil();
        $languageUtil = new LanguageUtil();
        $currencyUtil = new CurrencyUtil();

        // Create individual builders
        $customerBuilder = new CustomerBuilder(
            $addressBuilder,
            $addressUtil,
            $customerUtil,
            $languageUtil
        );

        $deliveryBuilder = new DeliveryBuilder(
            $addressBuilder,
            $addressUtil,
            $customerUtil,
            $languageUtil
        );

        $descriptionBuilder = new DescriptionBuilder();
        $gatewayInfoBuilder = new GatewayInfoBuilder();
        $paymentComponentBuilder = new PaymentComponentBuilder();
        $paymentOptionsBuilder = new PaymentOptionsBuilder($module);
        $pluginDetailsBuilder = new PluginDetailsBuilder($module);
        $secondChanceBuilder = new SecondChanceBuilder();

        // Create shopping cart builder with its dependencies
        $cartItemBuilder = new CartItemBuilder();
        $discountItemBuilder = new DiscountItemBuilder($module);
        $shippingItemBuilder = new ShippingItemBuilder($module);
        $wrappingItemBuilder = new WrappingItemBuilder($module);

        $shoppingCartBuilder = new ShoppingCartBuilder(
            [$cartItemBuilder, $discountItemBuilder, $shippingItemBuilder, $wrappingItemBuilder],
            $currencyUtil
        );

        $timeActiveBuilder = new TimeActiveBuilder();
        $tokenizationBuilder = new TokenizationBuilder();

        // Create OrderRequestBuilder with all builders (matching services.yml configuration)
        $orderRequestBuilders = [
            $customerBuilder,
            $deliveryBuilder,
            $descriptionBuilder,
            $gatewayInfoBuilder,
            $paymentComponentBuilder,
            $paymentOptionsBuilder,
            $pluginDetailsBuilder,
            $secondChanceBuilder,
            $shoppingCartBuilder,
            $timeActiveBuilder,
            $tokenizationBuilder
        ];

        return new OrderRequestBuilder($orderRequestBuilders, $currencyUtil);
    }
}
