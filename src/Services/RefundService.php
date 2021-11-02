<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\Services;

use Configuration;
use Currency;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\Description;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use MultiSafepay\PrestaShop\Helper\OrderMessageHelper;
use MultisafepayOfficial;
use Order;
use PrestaShopCollection;
use PrestaShopException;
use Tools;

/**
 * Class RefundService
 * @package MultiSafepay\PrestaShop\Services
 */
class RefundService
{
    /**
     * @var MultisafepayOfficial
     */
    private $module;

    /**
     * @var SdkService
     */
    private $sdkService;

    /**
     * @var PaymentOptionService
     */
    private $paymentOptionService;

    /**
     * RefundService constructor.
     *
     * @param MultisafepayOfficial $module
     * @param SdkService $sdkService
     * @param PaymentOptionService $paymentOptionService
     */
    public function __construct(
        MultisafepayOfficial $module,
        SdkService $sdkService,
        PaymentOptionService $paymentOptionService
    ) {
        $this->module               = $module;
        $this->sdkService           = $sdkService;
        $this->paymentOptionService = $paymentOptionService;
    }

    /**
     * @param Order $order
     * @param array $productList
     *
     * @return bool
     * @throws \PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function processRefund(Order $order, array $productList): bool
    {
        $transactionManager = $this->sdkService->getSdk()->getTransactionManager();
        $transaction        = $transactionManager->get($order->reference);

        $paymentOption = $this->paymentOptionService->getMultiSafepayPaymentOption(
            $transaction->getPaymentDetails()->getType()
        );

        // Do not process refunds for gateways that requires ShoppingCart
        if (!$paymentOption->canProcessRefunds()) {
            $this->handleMessage($order, "Refund for Order ID: $order->id has failed, because the Payment Method used for this Order does not support refunds.");

            return false;
        }

        $refundRequest = $transactionManager->createRefundRequest($transaction);
        $refundRequest->addDescription(Description::fromText('Refund order'));
        $refundData = $this->getRefundData($order, $productList);
        $refundRequest->addMoney(MoneyHelper::createMoney((float)$refundData['amount'], $refundData['currency']));

        try {
            $transactionManager->refund($transaction, $refundRequest);
        } catch (ApiException $exception) {
            $message = $exception->getMessage();
            LoggerHelper::logError("Error processing the refund for Order ID: $order->id. $message");
            $this->handleMessage($order, "Refund for Order ID: $order->id has failed.");

            return false;
        }

        $amount = $refundData['amount'];
        $currency = $refundData['currency'];
        $message = "A refund of $amount $currency has been processed for Order ID: $order->id";

        OrderMessageHelper::addMessage($order, $message);
        if (Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE')) {
            LoggerHelper::logInfo($message);
        }

        return true;
    }

    /**
     * @param Order $order
     * @param array $productList
     *
     * @return array
     */
    public function getRefundData(Order $order, $productList = []): array
    {
        $currency           = new Currency($order->id_currency);
        $refund             = [];
        $refund['currency'] = $currency->iso_code;
        $refund['amount']   = $this->getProductsRefundAmount($productList);
        $refund['amount']   += $this->getShippingRefundAmount($order);
        $refund['amount']   = Tools::ps_round($refund['amount'], 2, $order->round_mode);

        return $refund;
    }

    /**
     * @param array $productList
     *
     * @return float
     */
    public function getProductsRefundAmount($productList = []): float
    {
        $refundAmount = 0;
        foreach ($productList as $productListItem) {
            $refundAmount += $productListItem['amount'];
        }

        return $refundAmount;
    }

    /**
     * @param Order $order
     *
     * @return float
     */
    private function getShippingRefundAmount(Order $order): float
    {
        $cancelProduct = Tools::getValue('cancel_product');

        // If total shipping is being refunded (standard refund), then shipping_amount is equal to 0
        // and shipping value is 1.
        if (isset($cancelProduct['shipping']) && '1' === $cancelProduct['shipping']) {
            return (float)$order->total_shipping;
        }

        // If shipping amount is is being partially refunded, the "shipping" key is not set
        // and shipping_amount value reflects the total amount to be refunded.
        if (isset($cancelProduct['shipping_amount']) && '0' !== $cancelProduct['shipping_amount']) {
            return (float)$cancelProduct['shipping_amount'];
        }

        return 0;
    }

    /**
     * @return bool
     */
    public function isVoucherRefund(): bool
    {
        $cancelProduct = Tools::getValue('cancel_product');
        if (isset($cancelProduct['voucher']) && '1' === $cancelProduct['voucher']) {
            return true;
        }

        return false;
    }

    /**
     * @param Order $order
     * @param ?array $productList
     *
     * @return bool
     */
    public function isAllowedToRefund(Order $order, ?array $productList): bool
    {
        if (!$order->module || $this->module->name !== $order->module) {
            return false;
        }

        if (!isset($productList)) {
            $this->handleMessage($order, "Refund for Order ID: $order->id has failed, due to a missing productList");

            return false;
        }

        if ($this->isVoucherRefund()) {
            $this->handleMessage($order, "Refund for Order ID: $order->id will not be processed, due to a voucher being generated");

            return false;
        }

        if ($this->isSplitOrder($order->reference)) {
            $this->handleMessage($order, "Refund for Order ID: $order->id has failed, due to the order coming from a split shopping cart");

            return false;
        }

        return true;
    }

    /**
     * @param string $orderReference
     *
     * @return bool
     */
    public function isSplitOrder(string $orderReference): bool
    {
        /** @var PrestaShopCollection $orderCollection */
        $orderCollection = Order::getByReference($orderReference);

        return count($orderCollection->getResults()) > 1;
    }

    /**
     * @param Order $order
     * @param string $message
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function handleMessage(Order $order, string $message): void
    {
        OrderMessageHelper::addMessage($order, $message);
        LoggerHelper::logWarning($message);
        Tools::displayError($message);
    }
}
