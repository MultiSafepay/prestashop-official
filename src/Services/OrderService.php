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

use PaymentModule;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GoogleAnalytics;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\SecondChance;
use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use Multisafepay;
use ContextCore as PrestaShopContext;
use Order as PrestaShopOrder;
use PrestaShopCollection;
use CurrencyCore as PrestaShopCurrency;
use ConfigurationCore as PrestaShopConfiguration;
use MultiSafepay\PrestaShop\Services\GatewayInfoService;
use ObjectModel;

/**
 * Class OrderService
 *
 * @package MultiSafepay\PrestaShop\Services
 */
class OrderService
{

    /**
     * @var string
     */
    private $module_id;

    /**
     * @var string
     */
    private $secure_key;

    /**
     * OrderService constructor.
     * @param string $module_id
     * @param string $secure_key
     */
    public function __construct(string $module_id, string $secure_key)
    {
        $this->module_id = $module_id;
        $this->secure_key = $secure_key;
    }

    /**
     * @param PrestaShopCollection  $order_collection
     * @param string                $gateway_code
     * @param string                $type
     * @param array                 $gateway_info_vars
     * @return OrderRequest
     */
    public function createOrderRequest(PrestaShopCollection $order_collection, string $gateway_code = '', string $type = 'redirect', array $gateway_info_vars = null): OrderRequest
    {

        $order_request_arguments = $this->getOrderRequestArgumentsByOrderCollection($order_collection);
        $order_request = new OrderRequest();
        $order_request
            ->addOrderId((string) $order_request_arguments['order_id'])
            ->addMoney(MoneyHelper::createMoney((float) $order_request_arguments['order_total'], $order_request_arguments['currency_code']))
            ->addGatewayCode($gateway_code)
            ->addType($type)
            ->addPluginDetails($this->createPluginDetails())
            ->addDescriptionText($this->getOrderDescriptionText($order_request_arguments['order_id']))
            ->addCustomer((new CustomerService())->createCustomerDetails($order_collection->getFirst()))
            ->addPaymentOptions($this->createPaymentOptions($order_collection->getFirst()))
            ->addSecondsActive($this->getTimeActive())
            ->addSecondChance(( new SecondChance() )->addSendEmail(true));

        if ($order_request_arguments['shipping_total'] > 0) {
            $order_request->addDelivery((new CustomerService())->createDeliveryDetails($order_collection->getFirst()));
        }

        if (PrestaShopConfiguration::get('MULTISAFEPAY_GOOGLE_ANALYTICS_ID')) {
            $order_request->addGoogleAnalytics(( new GoogleAnalytics() )->addAccountId(PrestaShopConfiguration::get('MULTISAFEPAY_GOOGLE_ANALYTICS_ID')));
        }

        if ($gateway_info_vars) {
            $gateway_info = (new GatewayInfoService())->getGatewayInfo($gateway_code, $gateway_info_vars);
            $order_request->addGatewayInfo($gateway_info);
        }

        return $order_request;
    }


    /**
     * Return an array with values required in the OrderRequest object
     * and which should be common to the orders of a collections
     *
     * @param PrestaShopCollection $order_collection
     * @return array
     */
    public function getOrderRequestArgumentsByOrderCollection(PrestaShopCollection $order_collection): array
    {
        $order = $order_collection->getFirst();
        return array(
            'order_id'       => $order->reference,
            'order_total'    => $this->getOrderTotalByOrderCollection($order_collection),
            'shipping_total' => $this->getShippingTotalByOrderCollection($order_collection),
            'currency_code'  => PrestaShopCurrency::getIsoCodeById((int) $order->id_currency)
        );
    }

    /**
     * Return the sum of the totals of the orders within the given order collection.
     *
     * @param PrestaShopCollection $order_collection
     * @return float
     */
    public function getOrderTotalByOrderCollection(PrestaShopCollection $order_collection): float
    {
        $order_total = 0;
        foreach ($order_collection->getResults() as $order) {
            $order_total = $order_total + $order->total_paid;
        }
        return $order_total;
    }

    /**
     * Return the sum of the shipping totals of the orders within the given order collection.
     *
     * @param PrestaShopCollection $order_collection
     * @return float
     */
    public function getShippingTotalByOrderCollection(PrestaShopCollection $order_collection): float
    {
        $shipping_total = 0;
        foreach ($order_collection->getResults() as $order) {
            $shipping_total = $shipping_total + $order->total_shipping;
        }
        return $shipping_total;
    }

    /**
     * Return SecondsActive
     *
     * @return int
     */
    private function getTimeActive()
    {
        $time_active      = PrestaShopConfiguration::get('MULTISAFEPAY_TIME_ACTIVE_VALUE');
        $time_active_unit = PrestaShopConfiguration::get('MULTISAFEPAY_TIME_ACTIVE_UNIT');
        $time_active      = 30;
        $time_active_unit = 'days';
        if ((string) $time_active_unit === 'days') {
            $time_active = $time_active * 24 * 60 * 60;
        }
        if ((string)$time_active_unit === 'hours') {
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
            ->addPluginVersion(Multisafepay::getVersion())
            ->addShopRootUrl(PrestaShopContext::getContext()->shop->getBaseURL());
    }

    /**
     * @param   PrestaShopOrder $order
     * @return  PaymentOptions
     */
    private function createPaymentOptions(PrestaShopOrder $order): PaymentOptions
    {
        $payment_options        = new PaymentOptions();
        return $payment_options
            ->addNotificationMethod('GET')
            ->addNotificationUrl(PrestaShopContext::getContext()->link->getModuleLink('multisafepay', 'notification', array(), true))
            ->addCancelUrl(PrestaShopContext::getContext()->link->getModuleLink('multisafepay', 'cancel', array('id_cart' => $order->id_cart, 'id_reference' => $order->reference), true))
            ->addRedirectUrl(PrestaShopContext::getContext()->link->getPageLink('order-confirmation', null, PrestaShopContext::getContext()->language->id, 'id_cart=' . $order->id_cart . '&id_order=' . $order->id . '&id_module=' . $this->module_id . '&key=' . $this->secure_key));
    }

    /**
     * Return the order description.
     *
     * @param   string   $order_reference
     * @return  string   $order_description
     */
    private function getOrderDescriptionText(string $order_reference):string
    {
        $order_description = sprintf('Payment for order: %s', $order_reference);
        if (PrestaShopConfiguration::get('MULTISAFEPAY_ORDER_DESCRIPTION')) {
            $order_description = str_replace('{order_id}', $order_reference, PrestaShopConfiguration::get('MULTISAFEPAY_ORDER_DESCRIPTION'));
        }
        return $order_description;
    }
}
