<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\Services;

use Cart;
use Configuration;
use Context;
use Currency;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GoogleAnalytics;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\SecondChance;
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
     * OrderService constructor.
     *
     * @param MultisafepayOfficial $module
     * @param CustomerService $customerService
     * @param ShoppingCartService $shoppingCartService
     */
    public function __construct(
        MultisafepayOfficial $module,
        CustomerService $customerService,
        ShoppingCartService $shoppingCartService
    ) {
        $this->module              = $module;
        $this->customerService     = $customerService;
        $this->shoppingCartService = $shoppingCartService;
    }

    /**
     * @param PrestaShopCollection $orderCollection
     * @param BasePaymentOption $paymentOption
     *
     * @return OrderRequest
     */
    public function createOrderRequest(
        PrestaShopCollection $orderCollection,
        BasePaymentOption $paymentOption
    ): OrderRequest {

        /** @var Order $firstOrder */
        $firstOrder = $orderCollection->getFirst();

        /** @var Cart $shoppingCart */
        $shoppingCart = Cart::getCartByOrderId($firstOrder->id);

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
            ->addGatewayCode($paymentOption->getGatewayCode())
            ->addType($paymentOption->getTransactionType())
            ->addPluginDetails($this->createPluginDetails())
            ->addDescriptionText($this->getOrderDescriptionText($orderRequestArguments['order_id']))
            ->addCustomer($this->customerService->createCustomerDetails($firstOrder))
            ->addPaymentOptions($this->createPaymentOptions($firstOrder))
            ->addSecondsActive($this->getTimeActive())
            ->addSecondChance(
                (new SecondChance())->addSendEmail((bool)Configuration::get('MULTISAFEPAY_OFFICIAL_SECOND_CHANCE'))
            )
            ->addShoppingCart(
                $this->shoppingCartService->createShoppingCart(
                    $shoppingCart,
                    $orderRequestArguments['currency_code'],
                    $orderRequestArguments['round_type'],
                    $orderRequestArguments['weight_unit']
                )
            );

        if ($orderRequestArguments['shipping_total'] > 0) {
            $orderRequest->addDelivery((new CustomerService())->createDeliveryDetails($firstOrder));
        }

        if (Configuration::get('MULTISAFEPAY_OFFICIAL_GOOGLE_ANALYTICS_ID')) {
            $orderRequest->addGoogleAnalytics(
                (new GoogleAnalytics())->addAccountId(Configuration::get('MULTISAFEPAY_OFFICIAL_GOOGLE_ANALYTICS_ID'))
            );
        }

        $gatewayInfo = $paymentOption->getGatewayInfo($firstOrder, Tools::getAllValues());
        if ($gatewayInfo !== null) {
            $orderRequest->addGatewayInfo($gatewayInfo);
        }

        if ($paymentOption->allowTokenization()) {
            if ($this->shouldSaveToken()) {
                $orderRequest->addRecurringModel('cardOnFile');
            }
            if ($this->getToken() !== null && 'new' !== $this->getToken()) {
                $orderRequest->addRecurringId($this->getToken());
                $orderRequest->addType(BasePaymentOption::DIRECT_TYPE);
            }
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
     * @param Order $order
     *
     * @return  PaymentOptions
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    private function createPaymentOptions(Order $order): PaymentOptions
    {
        $paymentOptions = new PaymentOptions();

        return $paymentOptions
            ->addNotificationUrl(Context::getContext()->link->getModuleLink('multisafepayofficial', 'notification', [], true))
            ->addCancelUrl(
                Context::getContext()->link->getModuleLink(
                    'multisafepayofficial',
                    'cancel',
                    ['id_cart' => $order->id_cart, 'id_reference' => $order->reference],
                    true
                )
            )
            ->addRedirectUrl(
                Context::getContext()->link->getPageLink(
                    'order-confirmation',
                    null,
                    Context::getContext()->language->id,
                    'id_cart='.$order->id_cart.'&id_order='.$order->id.'&id_module='.$this->module->id.'&key='.Context::getContext()->customer->secure_key
                )
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
}
