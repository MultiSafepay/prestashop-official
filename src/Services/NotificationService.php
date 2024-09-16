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

namespace MultiSafepay\PrestaShop\Services;

use Cart;
use Configuration;
use Customer;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Helper\OrderMessageHelper;
use MultiSafepay\Util\Notification;
use MultisafepayOfficial;
use Order;
use OrderDetail;
use OrderHistory;
use OrderPayment;
use PrestaShopCollection;
use PrestaShopException;
use Tools;
use OrderInvoice;
use Cache;

/**
 * Class OrderService
 *
 * @package MultiSafepay\PrestaShop\Services
 */
abstract class NotificationService
{
    /**
     * @var MultisafepayOfficial
     */
    protected $module;

    /**
     * @var SdkService
     */
    protected $sdkService;

    /**
     * @var PaymentOptionService
     */
    protected $paymentOptionService;

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * NotificationService constructor.
     *
     * @param MultisafepayOfficial $module
     * @param SdkService $sdkService
     * @param PaymentOptionService $paymentOptionService
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function __construct(MultisafepayOfficial $module, SdkService $sdkService, PaymentOptionService $paymentOptionService, OrderService $orderService)
    {
        $this->module = $module;
        $this->sdkService = $sdkService;
        $this->paymentOptionService = $paymentOptionService;
        $this->orderService = $orderService;
    }

    /**
     * @param TransactionResponse $transaction
     * @param Cart $cart
     * @return void
     * @throws PrestaShopException
     */
    abstract public function processNotification(TransactionResponse $transaction, Cart $cart): void;

    /**
     * @param string $body
     *
     * @return TransactionResponse
     * @throws PrestaShopException
     */
    public function getTransactionFromBody(string $body): TransactionResponse
    {
        if (!Tools::getValue('transactionid') || empty(Tools::file_get_contents('php://input'))) {
            $message = "It seems the notification URL has been triggered but does not contain the required information";
            LoggerHelper::logWarning($message);
            throw new PrestaShopException($message);
        }

        if (!Notification::verifyNotification($body, $_SERVER['HTTP_AUTH'], $this->sdkService->getApiKey())) {
            $message = "Notification for transaction ID " . Tools::getValue('transactionid') . " has been received but is not valid";
            LoggerHelper::logWarning($message);
            throw new PrestaShopException($message);
        }

        try {
            return new TransactionResponse(json_decode($body, true), $body);
        } catch (ApiException $apiException) {
            LoggerHelper::logError($apiException->getMessage());
            throw new PrestaShopException($apiException->getMessage());
        }
    }

    /**
     * @param Order $order
     * @param TransactionResponse $transaction
     *
     * @return bool
     * @throws PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    public function shouldStatusBeUpdated(Order $order, TransactionResponse $transaction): bool
    {
        if (!$order->id) {
            $message = "It seems a notification is trying to process an order which does not exist. Transaction ID received is " . Tools::getValue('transactionid');
            LoggerHelper::logWarning($message);
            throw new PrestaShopException($message);
        }

        if ($order->module && $order->module !== 'multisafepayofficial') {
            $message = "It seems a notification is trying to process an order processed by another payment method. Transaction ID received is " . Tools::getValue('transactionid');
            LoggerHelper::logWarning($message);
            throw new PrestaShopException($message);
        }

        // If transaction status is initialized, but the current order status is PS_OS_OUTOFSTOCK_UNPAID
        // because this one changes quickly after order creation when there aren't products in stock
        if (Transaction::INITIALIZED === $transaction->getStatus() && (int)$order->current_state === (int)Configuration::get('PS_OS_OUTOFSTOCK_UNPAID')) {
            if (Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE')) {
                LoggerHelper::logInfo('A notification has been received but is being ignored since the transaction status is initialized, and the current order status is PS_OS_OUTOFSTOCK_UNPAID');
            }
            return false;
        }

        // If transaction status is completed, but the current order status is PS_OS_OUTOFSTOCK_PAID
        // because this one changes quickly when payment is completed and there aren't products in stock
        if (Transaction::COMPLETED === $transaction->getStatus() && (int)$order->current_state === (int)Configuration::get('PS_OS_OUTOFSTOCK_PAID')) {
            if (Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE')) {
                LoggerHelper::logInfo('A notification has been received but is being ignored since the transaction status is completed, and the current order status is PS_OS_OUTOFSTOCK_PAID');
            }
            return false;
        }

        // If the PrestaShop order status is considered a final status
        if ($this->isFinalStatus((int)$order->current_state)) {
            $message = "It seems a notification is trying to process an order which already have a final order status defined. For this reason notification is being ignored. Transaction ID received is " . Tools::getValue('transactionid') . " with status " . $transaction->getStatus();
            LoggerHelper::logWarning($message);
            OrderMessageHelper::addMessage($order, $message);
            return false;
        }

        // If the PrestaShop order status is the same as the one received in the notification
        if ((int)$order->current_state === (int)$this->getOrderStatusId($transaction->getStatus())) {
            return false;
        }

        return true;
    }

    /**
     * @param Order $order
     * @param TransactionResponse $transaction
     *
     * @throws PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    protected function processNotificationForOrder(Order $order, TransactionResponse $transaction): void
    {
        if (! $this->shouldStatusBeUpdated($order, $transaction)) {
            return;
        }

        // If the payment method of the PrestaShop order is not the same than the one received in the notification
        $paymentMethodName = $this->getPaymentMethodNameFromTransaction($transaction);
        if ($order->payment !== $paymentMethodName) {
            $this->updateOrderPaymentMethod($order, $paymentMethodName);
        }

        // Set new order status and set transaction id within the order information
        $this->updateOrderData($order, $transaction);

        if (Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE')) {
            LoggerHelper::logInfo('A notification has been processed for order ID: ' . $order->id . ' with status: ' . $transaction->getStatus() . ' and PSP ID: ' . $transaction->getTransactionId());
        }
    }

    /**
     * Change the order status
     *
     * @param Order $order
     * @param TransactionResponse $transaction
     * @return void
     */
    protected function updateOrderData(Order $order, TransactionResponse $transaction): void
    {
        $orderStatusId     = (int)$this->getOrderStatusId($transaction->getStatus());
        $history           = new OrderHistory();
        $history->id_order = $order->id;
        $history->changeIdOrderState($orderStatusId, $order->id, true);
        $history->addWithemail();

        if ('completed' === $transaction->getStatus()) {
            // Check in the order details list if the order contains product without stock
            if ($this->checkIfOrderContainsProductsWithoutStock($order)) {
                $this->processOrderStatusChangesForBackorders($order);
            }

            // Update OrderPayment with payment method name, amount, and PSP ID
            $this->updateOrderPaymentWithPaymentMethodName($order, $transaction);
        }
    }

    /**
     * OrderPayment object register by default the name of the PaymentModule
     * and not the name of the PaymentOption.
     *
     * @param Order $order
     * @param TransactionResponse $transaction
     */
    private function updateOrderPaymentWithPaymentMethodName(Order $order, TransactionResponse $transaction): void
    {
        $payments = $order->getOrderPaymentCollection();
        /** @var OrderPayment $payment */
        foreach ($payments->getResults() as $payment) {
            $payment->transaction_id = $transaction->getTransactionId();
            $payment->amount = $transaction->getAmount() / 100;
            $payment->payment_method = $order->payment;
            $payment->update();
        }
    }

    /**
     * @param Order $order
     */
    protected function processOrderStatusChangesForBackorders(Order $order): void
    {
        // Remove the cache is needed since OrderInvoice::getTotalPaid will return a wrong value, and for this reason
        // a new OrderPayment object will be generated within the method OrderHistory::changeIdOrderState()
        /** @var OrderInvoice[] $invoices */
        $invoices = $order->getInvoicesCollection();
        /** @var OrderInvoice $invoice */
        foreach ($invoices as $invoice) {
            $invoiceId = (int) $invoice->id;
            $invoiceDate = (string) $invoice->date_add;
            $cacheId = 'order_invoice_paid_' . $invoiceId;
            if (Cache::isStored($cacheId)) {
                Cache::clean($cacheId);
            }
        }

        // Change Order Status to out of stock paid.
        $history = new OrderHistory();
        $history->id_order = (int)$order->id;
        $history->changeIdOrderState((int)Configuration::get('PS_OS_OUTOFSTOCK_PAID'), $order, true);
        $history->addWithemail();

        // Set invoice_number and invoice date once again in order.
        if (isset($invoiceId) && isset($invoiceDate)) {
            $order->invoice_number = $invoiceId;
            $order->invoice_date = $invoiceDate;
            $order->save();
        }
    }

    /**
     * @param Order $order
     * @return bool
     * @throws PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    private function checkIfOrderContainsProductsWithoutStock(Order $order): bool
    {
        /** @var array $orderDetailList */
        $orderDetailList = $order->getOrderDetailList();
        foreach ($orderDetailList as $orderDetail) {
            $orderDetailObject = new OrderDetail($orderDetail['id_order_detail']);
            if (Configuration::get('PS_STOCK_MANAGEMENT') &&
                (
                    ($orderDetailObject->getStockState() || $orderDetailObject->product_quantity_in_stock <= 0) ||
                    ($orderDetailObject->product_quantity > $orderDetailObject->product_quantity_in_stock)
                )
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Update the order payment method if this one change after leave checkout page.
     *
     * @param Order $order
     * @param string $paymentMethodName
     */
    protected function updateOrderPaymentMethod(Order $order, string $paymentMethodName): void
    {
        // There is a special case for orders initialized with "Credit card" payment method.
        // Notification will return with the name of the gateway instead of credit card
        // However there is no need to add a note in these cases.
        if ($order->payment !== 'Credit card') {
            $message = 'Notification received with a different payment method for Order ID: ' . $order->id . ' and Order Reference: ' . $order->reference . ' on ' . date('d/m/Y H:i:s') . '. Payment method changed from ' . $order->payment . ' to ' . $paymentMethodName . '.';
            OrderMessageHelper::addMessage($order, $message);
            if (Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE')) {
                LoggerHelper::logInfo($message);
            }
        }

        // Update payment method
        $order->payment = $paymentMethodName;
        $order->save();
    }

    /**
     * Return the payment method name using the transaction information
     *
     * @param TransactionResponse $transaction
     * @return string
     */
    public function getPaymentMethodNameFromTransaction(TransactionResponse $transaction)
    {
        $gatewayCode = $transaction->getPaymentDetails()->getType();

        // When an order is being fully paid using a gift card
        if (strpos($gatewayCode, 'Coupon::') !== false) {
            $data = $transaction->getPaymentDetails()->getData();
            return $this->paymentOptionService->getMultiSafepayPaymentOption($data['coupon_brand'])->getFrontEndName();
        // When an order is being paid using multiple gift cards
        } elseif (strpos($gatewayCode, 'Coupon') !== false) {
            $data = $transaction->getPaymentDetails()->getData();
            $gatewayCodes = explode(';', $data['coupon_brand']);
            return $this->paymentOptionService->getMultiSafepayPaymentOption($gatewayCodes[0])->getFrontEndName();
        }

        return $this->paymentOptionService->getMultiSafepayPaymentOption($gatewayCode)->getFrontEndName();
    }

    /**
     * @param string $body
     * @return TransactionResponse
     */
    public function getTransactionFromPostNotification(string $body): TransactionResponse
    {
        try {
            $transaction = new TransactionResponse(json_decode($body, true), $body);
            return $transaction;
        } catch (ApiException $apiException) {
            LoggerHelper::logError($apiException->getMessage());
            throw new PrestaShopException($apiException->getMessage());
        }
    }

    /**
     * Return the order status id for the given transaction status
     *
     * @param string $transactionStatus
     * @return string
     */
    public function getOrderStatusId(string $transactionStatus): string
    {
        switch ($transactionStatus) {
            case Transaction::CANCELLED:
            case Transaction::EXPIRED:
            case Transaction::VOID:
                return Configuration::get('PS_OS_CANCELED');
            case Transaction::DECLINED:
                return Configuration::get('PS_OS_ERROR');
            case Transaction::COMPLETED:
                return Configuration::get('PS_OS_PAYMENT');
            case Transaction::UNCLEARED:
                return Configuration::get('MULTISAFEPAY_OFFICIAL_OS_UNCLEARED');
            case Transaction::REFUNDED:
                return Configuration::get('PS_OS_REFUND');
            case Transaction::PARTIAL_REFUNDED:
                return Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_REFUNDED');
            case Transaction::CHARGEDBACK:
                return Configuration::get('MULTISAFEPAY_OFFICIAL_OS_CHARGEBACK');
            case Transaction::SHIPPED:
                return Configuration::get('PS_OS_SHIPPING');
            case Transaction::INITIALIZED:
            default:
                return Configuration::get('MULTISAFEPAY_OFFICIAL_OS_INITIALIZED');
        }
    }

    /**
     * Return if the Order Status is final, therefore should not be changed anymore.
     *
     * @param int $orderStatus
     * @return bool
     */
    private function isFinalStatus(int $orderStatus): bool
    {
        $finalOrderStatuses = $this->settingToIntArray(Configuration::get('MULTISAFEPAY_OFFICIAL_FINAL_ORDER_STATUS'));

        return (in_array($orderStatus, $finalOrderStatuses, true));
    }

    /**
     * @return bool
     */
    protected function allowOrderCreation(string $status, string $transactionType)
    {
        switch ($status) {
            case Transaction::INITIALIZED:
                if ($transactionType === 'BANKTRANS' ||
                    $transactionType === 'MULTIBANCO'
                ) {
                    return true;
                }
                break;
            case Transaction::COMPLETED:
            case Transaction::UNCLEARED:
                return true;
        }
        return false;
    }

    /**
     * @param string $setting
     *
     * @return array
     */
    protected function settingToIntArray($setting): array
    {
        if (is_string($setting) && !empty($setting)) {
            return array_map('intval', json_decode($setting));
        }

        return [];
    }
}
