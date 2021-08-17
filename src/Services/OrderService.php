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

use Configuration as PrestaShopConfiguration;
use Context;
use Currency as PrestaShopCurrency;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GoogleAnalytics;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\SecondChance;
use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use Multisafepay;
use Order as PrestaShopOrder;
use PaymentModule;
use PrestaShopCollection;
use Tools;

/**
 * Class OrderService
 *
 * @package MultiSafepay\PrestaShop\Services
 */
class OrderService
{

    /**
     * @var Multisafepay
     */
    private $module;

    /**
     * @var CustomerService
     */
    private $customerService;

    /**
     * OrderService constructor.
     *
     * @param Multisafepay $module
     * @param CustomerService $customerService
     */
    public function __construct(Multisafepay $module, CustomerService $customerService)
    {
        $this->module          = $module;
        $this->customerService = $customerService;
    }

    /**
     * @param PrestaShopCollection $orderCollection
     * @param BasePaymentOption $paymentOption
     *
     * @return OrderRequest
     */
    public function createOrderRequest(PrestaShopCollection $orderCollection, BasePaymentOption $paymentOption): OrderRequest
    {
        $orderRequestArguments = $this->getOrderRequestArgumentsByOrderCollection($orderCollection);
        $orderRequest          = new OrderRequest();
        $orderRequest
            ->addOrderId((string)$orderRequestArguments['order_id'])
            ->addMoney(
                MoneyHelper::createMoney(
                    (float)$orderRequestArguments['order_total'],
                    $orderRequestArguments['currency_code']
                )
            )
            ->addGatewayCode($paymentOption->getPaymentOptionGatewayCode())
            ->addType($paymentOption->getTransactionType())
            ->addPluginDetails($this->createPluginDetails())
            ->addDescriptionText($this->getOrderDescriptionText($orderRequestArguments['order_id']))
            ->addCustomer($this->customerService->createCustomerDetails($orderCollection->getFirst()))
            ->addPaymentOptions($this->createPaymentOptions($orderCollection->getFirst()))
            ->addSecondsActive($this->getTimeActive())
            ->addSecondChance(( new SecondChance() )->addSendEmail((bool) PrestaShopConfiguration::get('MULTISAFEPAY_SECOND_CHANCE')));

        if ($orderRequestArguments['shipping_total'] > 0) {
            $orderRequest->addDelivery((new CustomerService())->createDeliveryDetails($orderCollection->getFirst()));
        }

        if (PrestaShopConfiguration::get('MULTISAFEPAY_GOOGLE_ANALYTICS_ID')) {
            $orderRequest->addGoogleAnalytics(
                (new GoogleAnalytics())->addAccountId(PrestaShopConfiguration::get('MULTISAFEPAY_GOOGLE_ANALYTICS_ID'))
            );
        }

        $gatewayInfoVars = Tools::getAllValues();
        if ($gatewayInfoVars) {
            $orderRequest->addGatewayInfo($paymentOption->getGatewayInfo($gatewayInfoVars));
        }

        return $orderRequest;
    }


    /**
     * Return an array with values required in the OrderRequest object
     * and which should be common to the orders of a collections
     *
     * @param PrestaShopCollection $orderCollection
     *
     * @return array
     */
    public function getOrderRequestArgumentsByOrderCollection(PrestaShopCollection $orderCollection): array
    {
        $order = $orderCollection->getFirst();
        return array(
            'order_id'       => $order->reference,
            'order_total'    => $this->getOrderTotalByOrderCollection($orderCollection),
            'shipping_total' => $this->getShippingTotalByOrderCollection($orderCollection),
            'currency_code'  => PrestaShopCurrency::getIsoCodeById((int)$order->id_currency),
        );
    }

    /**
     * Return the sum of the totals of the orders within the given order collection.
     *
     * @param PrestaShopCollection $orderCollection
     *
     * @return float
     */
    public function getOrderTotalByOrderCollection(PrestaShopCollection $orderCollection): float
    {
        $orderTotal = 0;
        foreach ($orderCollection->getResults() as $order) {
            $orderTotal = $orderTotal + $order->total_paid;
        }
        return $orderTotal;
    }

    /**
     * Return the sum of the shipping totals of the orders within the given order collection.
     *
     * @param PrestaShopCollection $orderCollection
     *
     * @return float
     */
    public function getShippingTotalByOrderCollection(PrestaShopCollection $orderCollection): float
    {
        $shippingTotal = 0;
        foreach ($orderCollection->getResults() as $order) {
            $shippingTotal = $shippingTotal + $order->total_shipping;
        }
        return $shippingTotal;
    }

    /**
     * Return SecondsActive
     *
     * @return int
     */
    private function getTimeActive(): int
    {
        $timeActive     = (int) PrestaShopConfiguration::get('MULTISAFEPAY_TIME_ACTIVE_VALUE');
        $timeActiveUnit = PrestaShopConfiguration::get('MULTISAFEPAY_TIME_ACTIVE_UNIT');
        if ((string)$timeActiveUnit === 'days') {
            $timeActive = $timeActive * 24 * 60 * 60;
        }
        if ((string)$timeActiveUnit === 'hours') {
            $timeActive = $timeActive * 60 * 60;
        }
        return $timeActive;
    }

    /**
     * @return PluginDetails
     */
    private function createPluginDetails()
    {
        $pluginDetails = new PluginDetails();
        return $pluginDetails
            ->addApplicationName('PrestaShop ')
            ->addApplicationVersion('PrestaShop: '._PS_VERSION_)
            ->addPluginVersion(Multisafepay::getVersion())
            ->addShopRootUrl(Context::getContext()->shop->getBaseURL());
    }

    /**
     * @param PrestaShopOrder $order
     *
     * @return  PaymentOptions
     */
    private function createPaymentOptions(PrestaShopOrder $order): PaymentOptions
    {
        $paymentOptions        = new PaymentOptions();
        return $paymentOptions
            ->addNotificationMethod('GET')
            ->addNotificationUrl(Context::getContext()->link->getModuleLink('multisafepay', 'notification', array(), true))
            ->addCancelUrl(Context::getContext()->link->getModuleLink('multisafepay', 'cancel', array('id_cart' => $order->id_cart, 'id_reference' => $order->reference), true))
            ->addRedirectUrl(Context::getContext()->link->getPageLink('order-confirmation', null, Context::getContext()->language->id, 'id_cart=' . $order->id_cart . '&id_order=' . $order->id . '&id_module=' . $this->module->id . '&key=' . Context::getContext()->customer->secure_key));
    }

    /**
     * Return the order description.
     *
     * @param   string   $orderReference
     */
    private function getOrderDescriptionText(string $orderReference): string
    {
        $orderDescription = sprintf('Payment for order: %s', $orderReference);
        if (PrestaShopConfiguration::get('MULTISAFEPAY_ORDER_DESCRIPTION')) {
            $orderDescription = str_replace('{order_reference}', $orderReference, PrestaShopConfiguration::get('MULTISAFEPAY_ORDER_DESCRIPTION'));
        }
        return $orderDescription;
    }
}
