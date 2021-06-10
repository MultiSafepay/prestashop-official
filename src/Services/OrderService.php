<?php declare(strict_types=1);
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\PrestaShop\Services;

use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GoogleAnalytics;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\SecondChance;
use MultiSafepay\PrestaShop\Utils\MoneyUtils;
use MultiSafepay;
use ContextCore as PrestaShopContext;
use OrderCore as PrestaShopOrder;
use CurrencyCore as PrestaShopCurrency;
use ConfigurationCore as PrestaShopConfiguration;

/**
 * Class OrderService
 *
 * @package MultiSafepay\PrestaShop\Services
 */
class OrderService
{

    /**
     * @var Order
     */
    private $order;

    /**
     * @var CustomerService
     */
    private $customer_service;

    /**
     * OrderService constructor.
     * @param PrestaShopOrder $order
     * @param $module_id
     * @param $secure_key
     */
    public function __construct(PrestaShopOrder $order, $module_id, $secure_key)
    {
        $this->order = $order;
        $this->module_id = $module_id;
        $this->secure_key = $secure_key;
        $this->customer_service = new CustomerService($this->order);
    }

    /**
     * @param string               $gateway_code
     * @param string               $type
     * @param GatewayInfoInterface $gateway_info
     * @return OrderRequest
     */
    public function createOrderRequest(string $gateway_code = '', string $type = 'redirect', GatewayInfoInterface $gateway_info = null): OrderRequest
    {
        $order_request = new OrderRequest();
        $order_request
            ->addOrderId((string) $this->order->id)
            ->addMoney(MoneyUtils::createMoney((float) $this->order->total_paid, PrestaShopCurrency::getIsoCodeById((int) $this->order->id_currency)))
            ->addGatewayCode($gateway_code)
            ->addType($type)
            ->addPluginDetails($this->createPluginDetails())
            ->addDescriptionText($this->getOrderDescriptionText($this->order->id))
            ->addCustomer($this->customer_service->createCustomerDetails())
            ->addPaymentOptions($this->createPaymentOptions($this->order))
            ->addSecondsActive($this->getTimeActive())
            ->addSecondChance(( new SecondChance() )->addSendEmail(true));

        if ($this->order->total_shipping > 0) {
            $order_request->addDelivery($this->customer_service->createDeliveryDetails());
        }
        if (PrestaShopConfiguration::get('MULTISAFEPAY_GOOGLE_ANALYTICS_ID')) {
            $order_request->addGoogleAnalytics(( new GoogleAnalytics() )->addAccountId(PrestaShopConfiguration::get('MULTISAFEPAY_GOOGLE_ANALYTICS_ID')));
        }
        if ($gateway_info) {
            $order_request->addGatewayInfo($gateway_info);
        }
        return $order_request;
    }

    /**
     * Return SecondsActive
     * @todo Create this fields in the settings
     *
     * @return int
     */
    private function getTimeActive()
    {
        $time_active      = PrestaShopConfiguration::get('MULTISAFEPAY_TIME_ACTIVE_VALUE');
        $time_active_unit = PrestaShopConfiguration::get('MULTISAFEPAY_TIME_ACTIVE_UNIT');
        $time_active      = 30;
        $time_active_unit = 'days';
        if ('days' === $time_active_unit) {
            $time_active = $time_active * 24 * 60 * 60;
        }
        if ('hours' === $time_active_unit) {
            $time_active = $time_active * 60 * 60;
        }
        return $time_active;
    }

    /**
     * @return PluginDetails
     */
    private function createPluginDetails()
    {
        $plugin_details = new PluginDetails();
        return $plugin_details
            ->addApplicationName('PrestaShop ')
            ->addApplicationVersion('PrestaShop: ' . _PS_VERSION_)
            ->addPluginVersion(MultiSafepay::getVersion())
            ->addShopRootUrl(PrestaShopContext::getContext()->shop->getBaseURL(true));
    }

    /**
     * @param   $order
     * @return  PaymentOptions
     */
    private function createPaymentOptions(): PaymentOptions
    {
        $payment_options        = new PaymentOptions();

        return $payment_options
            ->addNotificationMethod('GET')
            ->addNotificationUrl(PrestaShopContext::getContext()->link->getModuleLink('multisafepay', 'notification', array(), true))
            ->addCancelUrl(PrestaShopContext::getContext()->link->getModuleLink('multisafepay', 'cancel', array('id_cart' => $this->order->id_cart, 'id_order' => $this->order->id), true))
            ->addRedirectUrl(PrestaShopContext::getContext()->link->getPageLink('order-confirmation', null, PrestaShopContext::getContext()->language->id, 'id_cart=' . $this->order->id_cart . '&id_order=' . $this->order->id . '&id_module=' . $this->module_id . '&key=' . $this->secure_key));
    }

    /**
     * Return the order description.
     *
     * @param   int   $order_id
     * @return  string   $order_description
     */
    private function getOrderDescriptionText(int $order_id):string
    {
        $order_description = sprintf('Payment for order: %s', $order_id);
        if (PrestaShopConfiguration::get('MULTISAFEPAY_ORDER_DESCRIPTION')) {
            $order_description = str_replace('{order_id}', $order_id, PrestaShopConfiguration::get('MULTISAFEPAY_ORDER_DESCRIPTION'));
        }
        return $order_description;
    }
}
