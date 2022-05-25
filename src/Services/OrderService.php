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
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\PrestaShop\Services;

use Address;
use Cart;
use Configuration;
use Context;
use Country;
use Currency;
use Customer;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GoogleAnalytics;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\SecondChance;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultisafepayOfficial;
use Order;
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
     * @var MultisafepayOfficial
     */
    private $module;

    /**
     * @var CustomerService
     */
    private $customerService;

    /**
     * @var ShoppingCartService
     */
    private $shoppingCartService;

    /**
     * @var SdkService
     */
    private $sdkService;

    /**
     * @var string
     */
    private $paymentComponentApiToken = null;

    /**
     * OrderService constructor.
     *
     * @param MultisafepayOfficial $module
     * @param CustomerService $customerService
     * @param ShoppingCartService $cartService
     */
    public function __construct(
        MultisafepayOfficial $module,
        CustomerService $customerService,
        ShoppingCartService $cartService,
        SdkService $sdkService
    ) {
        $this->module              = $module;
        $this->customerService     = $customerService;
        $this->shoppingCartService = $cartService;
        $this->sdkService          = $sdkService;
    }

    /**
     * @param Cart $cart
     * @param Customer $customer
     * @param BasePaymentOption $paymentOption
     *
     * @return OrderRequest
     */
    public function createOrderRequest(
        Cart $cart,
        Customer $customer,
        BasePaymentOption $paymentOption,
        ?Order $order = null
    ): OrderRequest {
        $currencyCode = $this->getCurrencyIsoCodeById($cart->id_currency);
        $orderRequest = new OrderRequest();
        $orderRequest
            ->addOrderId((string)$cart->id)
            ->addMoney(
                MoneyHelper::createMoney(
                    (float)$cart->getOrderTotal(),
                    $currencyCode
                )
            )
            ->addGatewayCode($paymentOption->getGatewayCode())
            ->addType($paymentOption->getTransactionType())
            ->addPluginDetails($this->createPluginDetails())
            ->addDescriptionText($this->getOrderDescriptionText($order->reference ?? (string)$cart->id))
            ->addCustomer($this->customerService->createCustomerDetails($cart, $customer))
            ->addPaymentOptions($this->createPaymentOptions($cart, $customer->secure_key, $order))
            ->addSecondsActive($this->getTimeActive())
            ->addSecondChance(
                (new SecondChance())->addSendEmail((bool)Configuration::get('MULTISAFEPAY_OFFICIAL_SECOND_CHANCE'))
            )
            ->addData(['var2' => $cart->id]);

        if (!(bool)Configuration::get('MULTISAFEPAY_OFFICIAL_DISABLE_SHOPPING_CART')) {
            $orderRequest->addShoppingCart(
                $this->shoppingCartService->createShoppingCart(
                    $cart,
                    $currencyCode,
                    (int) Configuration::get('PS_ROUND_TYPE'),
                    Configuration::get('PS_WEIGHT_UNIT')
                )
            );
        }

        if ($cart->getTotalShippingCost() > 0) {
            $orderRequest->addDelivery($this->customerService->createDeliveryDetails($cart, $customer));
        }

        // If the order exist we use the order reference as transaction id
        if (isset($order)) {
            $orderRequest->addOrderId($order->reference);
        }

        if (Configuration::get('MULTISAFEPAY_OFFICIAL_GOOGLE_ANALYTICS_ID')) {
            $orderRequest->addGoogleAnalytics(
                (new GoogleAnalytics())->addAccountId(Configuration::get('MULTISAFEPAY_OFFICIAL_GOOGLE_ANALYTICS_ID'))
            );
        }

        $gatewayInfo = $paymentOption->getGatewayInfo($cart, Tools::getAllValues());
        if ($gatewayInfo !== null) {
            $orderRequest->addGatewayInfo($gatewayInfo);
        }

        if ($paymentOption->allowTokenization() && !$paymentOption->allowPaymentComponent()) {
            if ($this->shouldSaveToken()) {
                $orderRequest->addRecurringModel('cardOnFile');
            }
            if ($this->getToken() !== null && 'new' !== $this->getToken()) {
                $orderRequest->addRecurringModel('cardOnFile');
                $orderRequest->addRecurringId($this->getToken());
                $orderRequest->addType(BasePaymentOption::DIRECT_TYPE);
            }
        }

        if ($paymentOption->allowPaymentComponent() && Tools::getValue('payload')) {
            $orderRequest->addData(['payment_data' => ['payload' => Tools::getValue('payload')]]);
            $orderRequest->addType('direct');
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
        /** @var Order $order */
        $order = $orderCollection->getFirst();

        return [
            'order_id'       => $order->reference,
            'order_total'    => $this->getOrderTotalByOrderCollection($orderCollection),
            'shipping_total' => $this->getShippingTotalByOrderCollection($orderCollection),
            'currency_code'  => $this->getCurrencyIsoCodeById((int)$order->id_currency),
            'round_type'     => (int)$order->round_type,
            'weight_unit'    => Configuration::get('PS_WEIGHT_UNIT'),
        ];
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
            $orderTotal += $order->total_paid;
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
            $shippingTotal += $order->total_shipping;
        }

        return $shippingTotal;
    }

    /**
     * @param string|null $customerString
     * @param string|null $recurringModel
     *
     * @return array
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function createPaymentComponentOrder(?string $customerString, ?string $recurringModel): array
    {
        return
            [
                'debug'     => (bool)Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE') ?? false,
                'env'       => $this->sdkService->getTestMode() ? 'test' : 'live',
                'apiToken'  => $this->getPaymentComponentApiToken(),
                'orderData' => [
                    'currency'  => (new Currency(Context::getContext()->cart->id_currency))->iso_code,
                    'amount'    => MoneyHelper::priceToCents(
                        Context::getContext()->cart->getOrderTotal(true, Cart::BOTH)
                    ),
                    'customer'  => [
                        'locale'    => Tools::substr(Context::getContext()->language->getLocale(), 0, 2),
                        'country'   => (new Country(
                            (new Address((int)Context::getContext()->cart->id_address_invoice))->id_country
                        ))->iso_code,
                        'reference' => $customerString,
                    ],
                    'recurring' => [
                        'model' => $recurringModel,
                    ],
                    'template'  => [
                        'settings' => [
                            'embed_mode' => true,
                        ],
                    ],
                ],
            ];
    }

    /**
     * Return SecondsActive
     *
     * @return int
     */
    private function getTimeActive(): int
    {
        $timeActive     = (int)Configuration::get('MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_VALUE');
        $timeActiveUnit = Configuration::get('MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_UNIT');
        if ((string)$timeActiveUnit === 'days') {
            $timeActive *= 24 * 60 * 60;
        }
        if ((string)$timeActiveUnit === 'hours') {
            $timeActive *= 60 * 60;
        }

        return $timeActive;
    }

    /**
     * @return PluginDetails
     */
    public function createPluginDetails()
    {
        $pluginDetails = new PluginDetails();

        return $pluginDetails
            ->addApplicationName('PrestaShop ')
            ->addApplicationVersion('PrestaShop: '._PS_VERSION_)
            ->addPluginVersion($this->module->version)
            ->addShopRootUrl(Context::getContext()->shop->getBaseURL());
    }

    /**
     * @param Cart $cart
     * @param string $secureKeyCustomer
     *
     * @return PaymentOptions
     *
     * @codingStandardsIgnoreStart
     */
    private function createPaymentOptions(Cart $cart, string $secureKeyCustomer, ?Order $order): PaymentOptions
    {
        $paymentOptions = new PaymentOptions();

        $redirectUrl = Context::getContext()->link->getModuleLink('multisafepayofficial', 'callback', [], true);
        $cancelUrl = Context::getContext()->link->getPageLink('order', true, null, ['step' => '3']);

        if (isset($order)) {
            $redirectUrl = Context::getContext()->link->getPageLink(
                'order-confirmation',
                null,
                Context::getContext()->language->id,
                'id_cart='.$cart->id.'&id_order='.$order->id.'&id_module='.$this->module->id.'&key='.$secureKeyCustomer
            );

            $cancelUrl = Context::getContext()->link->getModuleLink(
                'multisafepayofficial',
                'cancel',
                ['id_cart' => $cart->id, 'id_reference' => $order->reference, 'key' => Context::getContext()->customer->secure_key],
                true
            );
        }

        return $paymentOptions
            ->addNotificationUrl(
                Context::getContext()->link->getModuleLink('multisafepayofficial', 'notification', [], true)
            )
            ->addCancelUrl(
                $cancelUrl
            )
            ->addRedirectUrl(
                $redirectUrl
            );
    }

    /**
     * Return the order description.
     *
     * @param string $orderReference
     */
    private function getOrderDescriptionText(string $orderReference): string
    {
        $orderDescription = sprintf('Payment for order: %s', $orderReference);
        if (Configuration::get('MULTISAFEPAY_OFFICIAL_ORDER_DESCRIPTION')) {
            $orderDescription = str_replace(
                '{order_reference}',
                $orderReference,
                Configuration::get('MULTISAFEPAY_OFFICIAL_ORDER_DESCRIPTION')
            );
        }

        return $orderDescription;
    }

    /**
     * @param int $currencyId
     *
     * @return string
     */
    private function getCurrencyIsoCodeById(int $currencyId): string
    {
        return (new Currency($currencyId))->iso_code;
    }

    /**
     * @return bool
     */
    private function shouldSaveToken(): bool
    {
        return (bool)Tools::getValue('saveToken', false) === true;
    }

    /**
     * @return string|null
     */
    private function getToken(): ?string
    {
        return Tools::getValue('selectedToken', null);
    }

    /**
     * @return string
     */
    public function getPaymentComponentApiToken(): string
    {
        if (!isset($this->paymentComponentApiToken)) {
            $this->paymentComponentApiToken = ($this->sdkService->getSdk()->getApiTokenManager()->get())->getApiToken();
        }

        return $this->paymentComponentApiToken;
    }

    /**
     * @param Cart $cart
     * @param int $orderStateId
     * @param float $amount
     * @param string $paymentMethod
     * @param string $secureKey
     *
     * @throws \PrestaShopException
     */
    public function validateOrder(
        Cart $cart,
        int $orderStateId,
        float $amount,
        string $paymentMethod,
        string $secureKey,
        array $extraVars = []
    ): void {
        // If order already exist we don't have to validate it again
        if ($cart->OrderExists()) {
            return;
        }

        $this->module->validateOrder(
            $cart->id,
            $orderStateId,
            $amount,
            $paymentMethod,
            null,
            array_merge(['send_email' => (Configuration::get('MULTISAFEPAY_OFFICIAL_CONFIRMATION_ORDER_EMAIL') ?? false)], $extraVars),
            $cart->id_currency, // @phpstan-ignore-line
            false,
            $secureKey // @phpstan-ignore-line
        );

        if (Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE')) {
            $orderCollection = new PrestaShopCollection('Order');
            $orderCollection->where('id_cart', '=', $cart->id);

            $ordersIds = $this->getOrdersIdsFromCollection($orderCollection);
            LoggerHelper::logInfo(
                'Order with Cart ID:'.$cart->id.' has been validated and as result the following orders IDS: '.implode(
                    ',',
                    $ordersIds
                ).' has been registered.'
            );
        }
    }

    /**
     * Return an array of Orders IDs for the given PrestaShopCollection
     *
     * @param PrestaShopCollection $orderCollection
     *
     * @return array
     */
    public function getOrdersIdsFromCollection(PrestaShopCollection $orderCollection): array
    {
        $ordersIds = [];
        foreach ($orderCollection->getResults() as $order) {
            $ordersIds[] = $order->id;
        }

        return $ordersIds;
    }
}
