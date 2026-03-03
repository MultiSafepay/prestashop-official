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

namespace MultiSafepay\PrestaShop\Services;

use Cache;
use Cart;
use Configuration;
use Exception;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\PrestaShop\Helper\ConfigHelper;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Helper\ManualCaptureHelper;
use MultiSafepay\PrestaShop\Helper\OrderMessageHelper;
use MultiSafepay\PrestaShop\Helper\OrderPaymentHelper;
use MultiSafepay\Util\Notification;
use MultisafepayOfficial;
use Order;
use OrderDetail;
use OrderHistory;
use OrderInvoice;
use OrderPayment;
use OrderState;
use PrestaShopCollection;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tools;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

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
     * @param OrderService $orderService
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
     * @throws InvalidArgumentException
     */
    public function getTransactionFromBody(string $body): TransactionResponse
    {
        $transactionId = Tools::getValue('transactionid');
        $message = 'It seems the notification URL has been triggered but does not contain the required information';

        if (!$transactionId) {
            LoggerHelper::log(
                'warning',
                $message
            );
            throw new PrestaShopException($message);
        }

        $orderCollection = Order::getByReference($transactionId);
        $firstOrder = $orderCollection->getFirst();
        if (!empty($firstOrder->id)) {
            $order = new Order($firstOrder->id);
            $orderId = (string)$order->id;
            $cartId = $order->id_cart;
        } else {
            $orderId = $cartId = null;
        }

        if (empty(Tools::file_get_contents('php://input'))) {
            LoggerHelper::log(
                'warning',
                $message,
                false,
                $orderId,
                $cartId
            );
            throw new PrestaShopException($message);
        }

        if (!(new Notification())->verify($body, $_SERVER['HTTP_AUTH'], $this->sdkService->getApiKey())) {
            $message = 'Notification for transaction ID ' . $transactionId . ' has been received but is not valid';
            LoggerHelper::log(
                'warning',
                $message,
                false,
                $orderId,
                $cartId
            );
            throw new PrestaShopException($message);
        }

        try {
            return new TransactionResponse(json_decode($body, true), $body);
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'error',
                $exception,
                'Error creating TransactionResponse from notification body',
                $orderId,
                $cartId
            );
            throw new PrestaShopException($exception->getMessage());
        }
    }

    /**
     * @param Order $order
     * @param TransactionResponse $transaction
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function shouldStatusBeUpdated(Order $order, TransactionResponse $transaction): bool
    {
        if (!$order->id) {
            $message = "It seems a notification is trying to process an order which does not exist. Transaction ID received is " . Tools::getValue('transactionid');
            LoggerHelper::log(
                'warning',
                $message
            );
            throw new PrestaShopException($message);
        }

        if ($order->module && $order->module !== 'multisafepayofficial') {
            $message = "It seems a notification is trying to process an order processed by another payment method. Transaction ID received is " . Tools::getValue('transactionid');
            LoggerHelper::log(
                'warning',
                $message,
                false,
                (string)$order->id ?: null,
                $order->id_cart ?: null
            );
            throw new PrestaShopException($message);
        }

        // If the transaction status is initialized, but the current order status is PS_OS_OUTOFSTOCK_UNPAID
        // because this one changes quickly after order creation when there are no products in stock
        if (Transaction::INITIALIZED === $transaction->getStatus() && (int)$order->current_state === (int)Configuration::get('PS_OS_OUTOFSTOCK_UNPAID')) {
            $message = 'A notification has been received but is being ignored since the transaction status is initialized, and the current order status is PS_OS_OUTOFSTOCK_UNPAID';
            LoggerHelper::log(
                'info',
                $message,
                true,
                (string)$order->id ?: null,
                $order->id_cart ?: null
            );
            return false;
        }

        // If transaction status is completed, but the current order status is PS_OS_OUTOFSTOCK_PAID
        // because this one changes quickly when payment is completed and there are no products in stock
        if (Transaction::COMPLETED === $transaction->getStatus() && (int)$order->current_state === (int)Configuration::get('PS_OS_OUTOFSTOCK_PAID')) {
            $message = 'A notification has been received but is being ignored since the transaction status is completed, and the current order status is PS_OS_OUTOFSTOCK_PAID';
            LoggerHelper::log(
                'info',
                $message,
                true,
                (string)$order->id ?: null,
                $order->id_cart ?: null
            );
            return false;
        }

        if ($this->isFinalStatus((int)$order->current_state)) {
            $message = 'It seems a notification is trying to process an order which already have a final order status defined. For this reason notification is being ignored. ';
            $message .= 'Transaction ID received is ' . Tools::getValue('transactionid') . ' with status ' . $transaction->getStatus();
            LoggerHelper::log(
                'warning',
                $message,
                false,
                (string)$order->id ?: null,
                $order->id_cart ?: null
            );
            OrderMessageHelper::addMessage($order, $message);
            return false;
        }

        $targetOrderState = $this->resolveTargetOrderStateId($transaction);
        $canOrderStatusBeUpdated = $this->canOrderStatusBeUpdated($order, $transaction, $targetOrderState);

        if (Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE')) {
            $currentOrderState = (int)$order->current_state;
            $message = sprintf(
                'Order #%s - Current state: %d, Target state: %d, Transaction status: %s, Should update: %s',
                $order->id,
                $currentOrderState,
                $targetOrderState,
                $transaction->getStatus(),
                $canOrderStatusBeUpdated ? 'YES' : 'NO'
            );
            LoggerHelper::log(
                'info',
                $message,
                false,
                (string)$order->id ?: null,
                $order->id_cart ?: null
            );
        }

        if (!$canOrderStatusBeUpdated) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the order status should be updated for the current notification.
     *
     * @param Order $order
     * @param TransactionResponse $transaction
     * @param int $targetOrderState
     * @return bool
     * @throws PrestaShopException
     */
    private function canOrderStatusBeUpdated(Order $order, TransactionResponse $transaction, int $targetOrderState): bool
    {
        $currentOrderState = (int)$order->current_state;

        if ($currentOrderState !== $targetOrderState) {
            return true;
        }

        // For partial captures, allow multiple entries only when the callback payload contains
        // new capture events that were not processed yet.
        return $this->hasUnprocessedPartialCaptureEvent($order, $transaction);
    }

    /**
     * Resolve the target order status ID for a notification, including manual capture specifics.
     *
     * @param TransactionResponse $transaction
     * @return int
     */
    private function resolveTargetOrderStateId(TransactionResponse $transaction): int
    {
        if (!ManualCaptureHelper::isManualCaptureTransaction($transaction)) {
            return $this->getOrderStatusId($transaction->getStatus());
        }

        if (ManualCaptureHelper::shouldBePartiallyCapturedStatus($transaction)) {
            return (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED');
        }

        if (ManualCaptureHelper::shouldBeAuthorizedStatus($transaction)) {
            return (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_AUTHORIZED');
        }

        if (ManualCaptureHelper::shouldBePaymentAcceptedStatus($transaction)) {
            return (int)Configuration::get('PS_OS_PAYMENT');
        }

        return $this->getOrderStatusId($transaction->getStatus());
    }

    /**
     * Check if the callback payload contains new partial capture events that are not processed yet.
     *
     * @param Order $order
     * @param TransactionResponse $transaction
     * @return bool
     * @throws PrestaShopException
     */
    private function hasUnprocessedPartialCaptureEvent(Order $order, TransactionResponse $transaction): bool
    {
        if (!ManualCaptureHelper::isManualCaptureTransaction($transaction)
            || !ManualCaptureHelper::shouldBePartiallyCapturedStatus($transaction)) {
            return false;
        }

        $partialCapturedStatusId = (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED');
        if ($partialCapturedStatusId <= 0) {
            return false;
        }

        $captureEventsInPayload = $this->getCaptureEventsCountFromPayload($transaction);

        // If the callback payload does not include related capture events, keep legacy behavior.
        if ($captureEventsInPayload <= 0) {
            return false;
        }

        $processedPartialCaptureEvents = $this->getOrderHistoryStateCount((int)$order->id, $partialCapturedStatusId);

        return $processedPartialCaptureEvents < $captureEventsInPayload;
    }

    /**
     * Count capture events included in the callback payload.
     *
     * @param TransactionResponse $transaction
     * @return int
     */
    private function getCaptureEventsCountFromPayload(TransactionResponse $transaction): int
    {
        $transactionData = $transaction->getData();
        if ($transactionData === []
            || !isset($transactionData['related_transactions'])
            || !is_array($transactionData['related_transactions'])) {
            return 0;
        }

        $captureEventsCount = 0;
        foreach ($transactionData['related_transactions'] as $relatedTransaction) {
            if (!is_array($relatedTransaction) || !isset($relatedTransaction['type'])) {
                continue;
            }

            if (Tools::strtolower((string)$relatedTransaction['type']) === 'capture') {
                $captureEventsCount++;
            }
        }

        return $captureEventsCount;
    }

    /**
     * Count history entries for a specific order state.
     *
     * @param int $orderId
     * @param int $orderStateId
     * @return int
     * @throws PrestaShopException
     */
    private function getOrderHistoryStateCount(int $orderId, int $orderStateId): int
    {
        if ($orderId <= 0 || $orderStateId <= 0) {
            return 0;
        }

        $orderHistoryCollection = new PrestaShopCollection('OrderHistory');
        $orderHistoryCollection->where('id_order', '=', $orderId);
        $orderHistoryCollection->where('id_order_state', '=', $orderStateId);

        return $orderHistoryCollection->count();
    }

    /**
     * @param Order $order
     * @param Cart $cart
     * @param TransactionResponse $transaction
     *
     * @throws PrestaShopException
     */
    protected function processNotificationForOrder(Order $order, Cart $cart, TransactionResponse $transaction): void
    {
        if (! $this->shouldStatusBeUpdated($order, $transaction)) {
            return;
        }

        // If the payment method of the PrestaShop order is different from the one received in the notification
        $paymentMethodName = $this->getPaymentMethodNameFromTransaction($transaction, $order->id_lang ?: $cart->id_lang ?: null);
        if ($order->payment !== $paymentMethodName) {
            $this->updateOrderPaymentMethod($order, $paymentMethodName);
        }

        // Set a new order status and set transaction id within the order information
        $this->updateOrderData($order, $transaction);
        LoggerHelper::log(
            'info',
            'A notification has been processed with status: ' . $transaction->getStatus() . ' and PSP ID: ' . $transaction->getTransactionId(),
            true,
            (string)$order->id ?: null,
            $order->id_cart ?: null
        );
    }

    /**
     * Update the order data for existing orders
     *
     * Used when 'Create order before payment' is enabled
     *
     * @param Order $order
     * @param TransactionResponse $transaction
     * @return void
     * @throws PrestaShopException
     */
    protected function updateOrderData(Order $order, TransactionResponse $transaction): void
    {
        // Check if this is a manual capture transaction and delegate to manual capture processing
        if (ManualCaptureHelper::isManualCaptureTransaction($transaction)) {
            $this->existingOrderProcessManualCaptureNotification($order, $transaction);
            return;
        }

        $orderStatusId = $this->getOrderStatusId($transaction->getStatus());
        $history = new OrderHistory();
        $history->id_order = $order->id;

        // Standard flow for other transaction statuses
        $history->changeIdOrderState($orderStatusId, $order->id, true);
        $history->id_order_state = $orderStatusId;
        $history->addWithemail();

        if ($transaction->getStatus() === 'completed') {
            // Refresh order to get any payments created by changeIdOrderState
            $order = new Order($order->id);

            // Update existing payment with transaction info or create if not exists
            OrderPaymentHelper::syncOrderPaymentWithTransaction($order, $transaction);

            // Handle backorders if needed
            if ($this->checkIfOrderContainsProductsWithoutStock($order)) {
                $this->processOrderStatusChangesForBackorders($order);
            }
        }
    }

    /**
     * Register a payment row using the captured amount resolved from the callback payload.
     *
     * @param Order $order
     * @param TransactionResponse $transaction
     * @return void
     * @throws PrestaShopException
     */
    private function registerManualCapturePaymentFromCallback(Order $order, TransactionResponse $transaction): void
    {
        $capturedAmountInCents = ManualCaptureHelper::getCapturedAmountForManualCapturePaymentInCents($transaction);
        if ($capturedAmountInCents === null || $capturedAmountInCents <= 0) {
            return;
        }

        OrderPaymentHelper::createOrderPaymentWithCustomAmount(
            $order,
            $transaction,
            $capturedAmountInCents / 100,
            true
        );
    }

    /**
     * Determine if the manual-capture callback should append a payment row instead of syncing all rows.
     *
     * @param Order $order
     * @param TransactionResponse $transaction
     * @param bool $paymentCreatedByStateChange
     * @return bool
     */
    private function shouldAppendManualCapturePayment(
        Order $order,
        TransactionResponse $transaction,
        bool $paymentCreatedByStateChange = false
    ): bool {
        if ($paymentCreatedByStateChange) {
            return false;
        }

        $capturedAmountInCents = ManualCaptureHelper::getCapturedAmountForManualCapturePaymentInCents($transaction);
        if ($capturedAmountInCents === null || $capturedAmountInCents <= 0) {
            return false;
        }

        $transactionTotalAmountInCents = (int)$transaction->getAmount();
        if ($transactionTotalAmountInCents <= 0) {
            LoggerHelper::log(
                'warning',
                'Manual capture: invalid transaction total amount in cents; skipping append-payment decision. Total: '
                . $transactionTotalAmountInCents,
                false,
                (string)$order->id ?: null,
                $order->id_cart ?: null
            );

            return false;
        }

        $payments = $order->getOrderPaymentCollection();

        if ($payments->count() === 0) {
            return true;
        }

        if ($capturedAmountInCents !== $transactionTotalAmountInCents) {
            return true;
        }

        return $payments->count() > 1;
    }

    /**
     * Determine whether full sync is safe in manual-capture completed flow.
     *
     * Full sync rewrites all payment rows and must only run for single-step captures.
     *
     * @param int $paymentsCountBeforeStateChange
     * @param bool $paymentCreatedByStateChange
     * @return bool
     */
    private function shouldSyncSingleStepManualCapturePayment(
        int $paymentsCountBeforeStateChange,
        bool $paymentCreatedByStateChange
    ): bool {
        return $paymentsCountBeforeStateChange === 0 && !$paymentCreatedByStateChange;
    }

    /**
     * Process notification for manual capture transactions
     *
     * @param Order $order
     * @param TransactionResponse $transaction
     * @return void
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function existingOrderProcessManualCaptureNotification(Order $order, TransactionResponse $transaction): void
    {
        $history = new OrderHistory();
        $history->id_order = $order->id;

        $financialStatus = $transaction->getFinancialStatus();

        // Case 1: financial_status = "initialized" => Authorized
        if (ManualCaptureHelper::shouldBeAuthorizedStatus($transaction)) {
            $authorizedStatusId = (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_AUTHORIZED');

            ManualCaptureHelper::addManualCaptureNote($order, $transaction);

            if ($this->isValidOrderStateId($authorizedStatusId)) {
                $history->id_order_state = $authorizedStatusId;
                $history->addWithemail();

                LoggerHelper::log(
                    'info',
                    'Manual capture: Order set to "MultiSafepay authorized". Status: ' . $transaction->getStatus() .
                    ', Financial status: ' . $financialStatus,
                    true,
                    (string)$order->id ?: null,
                    $order->id_cart ?: null
                );
            } else {
                LoggerHelper::log(
                    'warning',
                    'Manual capture: authorized callback detected but MULTISAFEPAY_OFFICIAL_OS_AUTHORIZED is missing/invalid. '
                    . 'Skipped order-state change; note still registered. Status: '
                    . $transaction->getStatus() . ', Financial status: ' . $financialStatus,
                    true,
                    (string)$order->id ?: null,
                    $order->id_cart ?: null
                );
            }

            return;
        }

        // Case 2: financial_status = "completed" => Payment Accepted
        if (ManualCaptureHelper::shouldBePaymentAcceptedStatus($transaction)) {
            $acceptedPaymentId = (int)Configuration::get('PS_OS_PAYMENT');
            $isManualPartialCaptureContext = $this->isManualPartialCaptureContext($order, $transaction);
            $paymentIdsBeforeStateChange = $this->getOrderPaymentIds($order);
            $paymentsCountBeforeStateChange = $order->getOrderPaymentCollection()->count();

            // Change state first (may create payment if invoices exist)
            $history->changeIdOrderState($acceptedPaymentId, $order->id, true);

            // Normalize rows immediately if the state change already created payment rows.
            // This avoids temporary generic values (e.g., payment method "MultiSafepay")
            // in the back office while the notification is still being processed.
            if ($isManualPartialCaptureContext) {
                $orderAfterStateChange = new Order($order->id);
                $paymentIdsAfterStateChange = $this->getOrderPaymentIds($orderAfterStateChange);
                $newPaymentRowsCreated = count(array_diff_key($paymentIdsAfterStateChange, $paymentIdsBeforeStateChange)) > 0;

                if ($newPaymentRowsCreated) {
                    $this->syncNewlyCreatedPaymentsWithTransactionInfo(
                        $orderAfterStateChange,
                        $transaction,
                        $paymentIdsBeforeStateChange
                    );
                    $this->normalizeManualCapturePaymentRows($orderAfterStateChange, $transaction);
                }
            }

            $history->id_order_state = $acceptedPaymentId;
            $history->addWithemail();

            // Refresh order to get any payments created by changeIdOrderState
            $order = new Order($order->id);
            $paymentsCountAfterStateChange = $order->getOrderPaymentCollection()->count();
            $paymentCreatedByStateChange = $paymentsCountAfterStateChange > $paymentsCountBeforeStateChange;

            if ($isManualPartialCaptureContext) {
                if ($paymentCreatedByStateChange) {
                    $this->syncNewlyCreatedPaymentsWithTransactionInfo(
                        $order,
                        $transaction,
                        $paymentIdsBeforeStateChange
                    );
                }

                $this->normalizeManualCapturePaymentRows($order, $transaction);

                // Keep split partial-capture payments intact by appending the payment row when needed.
                if ($this->shouldAppendManualCapturePayment($order, $transaction, $paymentCreatedByStateChange)) {
                    $this->registerManualCapturePaymentFromCallback($order, $transaction);
                } elseif ($this->shouldSyncSingleStepManualCapturePayment(
                    $paymentsCountBeforeStateChange,
                    $paymentCreatedByStateChange
                )) {
                    // Standard behavior for full single-step capture.
                    OrderPaymentHelper::syncOrderPaymentWithTransaction($order, $transaction);
                }
            } else {
                // Keep previous behavior for non-partial manual-capture contexts.
                OrderPaymentHelper::syncOrderPaymentWithTransaction($order, $transaction);
            }

            ManualCaptureHelper::addFinishedManualCaptureNote($order, $transaction);

            if ($this->checkIfOrderContainsProductsWithoutStock($order)) {
                $paymentIdsBeforeBackorderStateChange = [];
                if ($isManualPartialCaptureContext) {
                    $paymentIdsBeforeBackorderStateChange = $this->getOrderPaymentIds($order);
                }

                $this->processOrderStatusChangesForBackorders($order);

                if ($isManualPartialCaptureContext) {
                    $order = new Order($order->id);
                    $this->syncNewlyCreatedPaymentsWithTransactionInfo(
                        $order,
                        $transaction,
                        $paymentIdsBeforeBackorderStateChange
                    );
                    $this->normalizeManualCapturePaymentRows($order, $transaction);
                }
            }

            LoggerHelper::log(
                'info',
                'Manual capture: Order set to "Payment Accepted". Status: ' . $transaction->getStatus() .
                ', Financial status: ' . $financialStatus,
                true,
                (string)$order->id ?: null,
                $order->id_cart ?: null
            );
            return;
        }

        // Case 2b: callback indicates partial capture => MultiSafepay partially captured
        if (ManualCaptureHelper::shouldBePartiallyCapturedStatus($transaction)) {
            $partialCapturedStatusId = (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED');

            // Ensure a note is always written even if payment-row registration or state change fails.
            ManualCaptureHelper::addPartialManualCaptureNote($order, $transaction);
            $paymentRowRegistered = false;

            try {
                $this->registerManualCapturePaymentFromCallback($order, $transaction);
                $paymentRowRegistered = true;
            } catch (Exception $exception) {
                LoggerHelper::logException(
                    'warning',
                    $exception,
                    'Manual capture: failed to register partial-capture payment row from callback.',
                    (string)$order->id ?: null,
                    $order->id_cart ?: null
                );
            }

            if ($this->isValidOrderStateId($partialCapturedStatusId)) {
                $history->id_order_state = $partialCapturedStatusId;
                $history->addWithemail();

                LoggerHelper::log(
                    'info',
                    'Manual capture: Order set to "MultiSafepay partially captured". Status: '
                    . $transaction->getStatus() . ', Financial status: ' . $financialStatus,
                    true,
                    (string)$order->id ?: null,
                    $order->id_cart ?: null
                );
            } else {
                LoggerHelper::log(
                    'warning',
                    'Manual capture: partial capture detected but MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED is missing/invalid. '
                    . 'Skipped order-state change; note registered and payment row '
                    . ($paymentRowRegistered ? 'registered' : 'could not be registered')
                    . '. Status: '
                    . $transaction->getStatus() . ', Financial status: ' . $financialStatus,
                    true,
                    (string)$order->id ?: null,
                    $order->id_cart ?: null
                );
            }

            return;
        }

        // Case 3: Manual capture canceled - Special handling to avoid a generic message
        if ($transaction->getStatus() === Transaction::CANCELLED ||
            $transaction->getStatus() === Transaction::VOID) {
            $cancelledStatusId = (int)Configuration::get('PS_OS_CANCELED');
            $history->id_order_state = $cancelledStatusId;
            $history->addWithemail();

            // Add a custom message for manual capture cancellation
            $message = 'Manual capture was successfully cancelled to release the funds back.';
            OrderMessageHelper::addMessage($order, $message);

            LoggerHelper::log(
                'info',
                'Manual capture: Order cancelled. ' . $message,
                true,
                (string)$order->id ?: null,
                $order->id_cart ?: null
            );
        }
    }

    /**
     * Get order payment IDs indexed by id for fast lookup.
     *
     * @param Order $order
     * @return array<int, bool>
     */
    private function getOrderPaymentIds(Order $order): array
    {
        $paymentIds = [];

        /** @var OrderPayment $payment */
        foreach ($order->getOrderPaymentCollection()->getResults() as $payment) {
            $paymentId = (int)$payment->id;
            if ($paymentId > 0) {
                $paymentIds[$paymentId] = true;
            }
        }

        return $paymentIds;
    }

    /**
     * Validate that an order state ID points to an existing OrderState.
     *
     * @param int $orderStateId
     * @return bool
     */
    private function isValidOrderStateId(int $orderStateId): bool
    {
        if ($orderStateId <= 0) {
            return false;
        }

        try {
            $orderState = new OrderState($orderStateId);
        } catch (Exception $exception) {
            return false;
        }

        return Validate::isLoadedObject($orderState);
    }

    /**
     * Sync payment method and transaction ID for payment rows created by state change.
     *
     * @param Order $order
     * @param TransactionResponse $transaction
     * @param array<int, bool> $paymentIdsBeforeStateChange
     * @return void
     * @throws PrestaShopException
     */
    private function syncNewlyCreatedPaymentsWithTransactionInfo(
        Order $order,
        TransactionResponse $transaction,
        array $paymentIdsBeforeStateChange
    ): void {
        $paymentMethodName = $this->getPaymentMethodNameFromTransaction($transaction, $order->id_lang ?: null);
        $transactionId = $transaction->getTransactionId();

        /** @var OrderPayment $payment */
        foreach ($order->getOrderPaymentCollection()->getResults() as $payment) {
            $paymentId = (int)$payment->id;
            if ($paymentId <= 0 || isset($paymentIdsBeforeStateChange[$paymentId])) {
                continue;
            }

            $currentTransactionId = trim((string)$payment->transaction_id);
            $currentPaymentMethod = trim((string)$payment->payment_method);

            if ($currentTransactionId === $transactionId && $currentPaymentMethod === $paymentMethodName) {
                continue;
            }

            $payment->transaction_id = $transactionId;
            $payment->payment_method = $paymentMethodName;
            $payment->update();
        }
    }

    /**
     * Detect whether the current manual-capture callback belongs to the partial-capture flow.
     *
     * @param Order $order
     * @param TransactionResponse $transaction
     * @return bool
     * @throws PrestaShopException
     */
    private function isManualPartialCaptureContext(Order $order, TransactionResponse $transaction): bool
    {
        if (!ManualCaptureHelper::isManualCaptureTransaction($transaction)) {
            return false;
        }

        if (ManualCaptureHelper::shouldBePartiallyCapturedStatus($transaction)) {
            return true;
        }

        $partialCapturedStatusId = (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED');
        if ($partialCapturedStatusId <= 0) {
            return false;
        }

        if ((int)$order->current_state === $partialCapturedStatusId) {
            return true;
        }

        return $this->getOrderHistoryStateCount((int)$order->id, $partialCapturedStatusId) > 0;
    }

    /**
     * Normalize manual-capture payment rows created with generic data.
     *
     * @param Order $order
     * @param TransactionResponse $transaction
     * @return void
     * @throws PrestaShopException
     */
    private function normalizeManualCapturePaymentRows(Order $order, TransactionResponse $transaction): void
    {
        $paymentMethodName = $this->getPaymentMethodNameFromTransaction($transaction, $order->id_lang ?: null);
        $transactionId = trim((string)$transaction->getTransactionId());

        if ($transactionId === '') {
            return;
        }

        /** @var OrderPayment $payment */
        foreach ($order->getOrderPaymentCollection()->getResults() as $payment) {
            $currentTransactionId = trim((string)$payment->transaction_id);
            $currentPaymentMethod = trim((string)$payment->payment_method);

            $hasGenericMethod = $currentPaymentMethod === '' || Tools::strtolower($currentPaymentMethod) === 'multisafepay';
            $hasMissingTransaction = $currentTransactionId === '';

            if (!$hasGenericMethod && !$hasMissingTransaction) {
                continue;
            }

            if ($hasMissingTransaction) {
                $payment->transaction_id = $transactionId;
            }

            if ($hasGenericMethod) {
                $payment->payment_method = $paymentMethodName;
            }

            $payment->update();
        }
    }

    /**
     * @param Order $order
     * @throws PrestaShopException
     */
    protected function processOrderStatusChangesForBackorders(Order $order): void
    {
        // Remove the cache is needed since OrderInvoice::getTotalPaid will return a wrong value, and for this reason
        // a new OrderPayment object will be generated within the method OrderHistory::changeIdOrderState()
        /** @var OrderInvoice[] $invoices */
        $invoices = $order->getInvoicesCollection();
        foreach ($invoices as $invoice) {
            $invoiceId = (int) $invoice->id;
            $invoiceDate = (string) $invoice->date_add;
            $cacheId = 'order_invoice_paid_' . $invoiceId;
            if (Cache::isStored($cacheId)) {
                Cache::clean($cacheId);
            }
        }

        // Change Order Status to out-of-stock paid.
        $history = new OrderHistory();
        $history->id_order = (int)$order->id;
        $history->changeIdOrderState((int)Configuration::get('PS_OS_OUTOFSTOCK_PAID'), $order, true);
        $history->id_order_state = (int)Configuration::get('PS_OS_OUTOFSTOCK_PAID');
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
     */
    private function checkIfOrderContainsProductsWithoutStock(Order $order): bool
    {
        $backorderStatusIsNotSupported = version_compare(_PS_VERSION_, '8.1.0', '<');
        $backorderStatusEnabled = $backorderStatusIsNotSupported || Configuration::get('PS_ENABLE_BACKORDER_STATUS');

        $orderDetailList = $order->getOrderDetailList();
        foreach ($orderDetailList as $orderDetail) {
            $orderDetailObject = new OrderDetail($orderDetail['id_order_detail']);
            if (Configuration::get('PS_STOCK_MANAGEMENT') && $backorderStatusEnabled &&
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
     * Update the order payment method if this one changes after leave checkout page.
     *
     * @param Order $order
     * @param string $paymentMethodName
     * @throws PrestaShopException
     */
    protected function updateOrderPaymentMethod(Order $order, string $paymentMethodName): void
    {
        // There is a special case for orders initialized with the "Credit card" payment method.
        // Notification will return with the name of the gateway instead of a credit card;
        // however, there is no need to add a note in these cases.
        if ($order->payment !== 'Credit card') {
            $message = 'Notification received with a different payment method for Order ID: ' . $order->id . ' and Order Reference: ' . $order->reference . ' on ' . date('d/m/Y H:i:s') . '. Payment method changed from ' . $order->payment . ' to ' . $paymentMethodName . '.';
            OrderMessageHelper::addMessage($order, $message);
            LoggerHelper::log(
                'info',
                $message,
                true,
                (string)$order->id ?: null,
                $order->id_cart ?: null
            );
        }

        // Update payment method
        $order->payment = $paymentMethodName;
        $order->save();
    }

    /**
     * Get Payment Method Name from Transaction Information
     *
     * Returns the localized payment method name based on transaction details,
     * including wallet transactions and special cases like grouped credit cards
     * and gift card coupons.
     *
     * @param TransactionResponse $transaction
     * @param int|null $langId
     * @return string
     *
     * @api
     * @see NotificationService
     * @see NotExistingOrderNotificationService
     */
    public function getPaymentMethodNameFromTransaction(TransactionResponse $transaction, ?int $langId = null): string
    {
        $gatewayCode = $transaction->getPaymentDetails()->getType();

        $walletGatewayCode = $this->getWalletGatewayCodeFromTransaction($transaction);
        if (!empty($walletGatewayCode) && $walletGatewayCode !== $gatewayCode) {
            $walletName = $this->getPaymentOptionFrontEndNameByGatewayCode($walletGatewayCode, $langId);
            $underlyingMethodName = $this->getPaymentOptionFrontEndNameByGatewayCode($gatewayCode, $langId);

            return $walletName . ' (' . $underlyingMethodName . ')';
        }

        if (in_array($gatewayCode, PaymentOptionService::CREDIT_CARD_GATEWAYS, true) &&
            Configuration::get('MULTISAFEPAY_OFFICIAL_GROUP_CREDITCARDS')
        ) {
            $gatewayCode = 'CREDITCARD';
        }

        // When an order is being fully paid using a gift card
        if (strpos($gatewayCode, 'Coupon::') !== false) {
            $data = $transaction->getPaymentDetails()->getData();
            return $this->getPaymentOptionFrontEndNameByGatewayCode($data['coupon_brand'], $langId);
        // When an order is being paid using multiple gift cards
        } elseif (strpos($gatewayCode, 'Coupon') !== false) {
            $data = $transaction->getPaymentDetails()->getData();
            $gatewayCodes = explode(';', $data['coupon_brand']);
            return $this->getPaymentOptionFrontEndNameByGatewayCode($gatewayCodes[0], $langId);
        }

        return $this->getPaymentOptionFrontEndNameByGatewayCode($gatewayCode, $langId);
    }

    /**
     * Resolve the localized payment option name for a gateway code.
     *
     * Falls back to the original gateway code when the payment option
     * is not available in the current payment method list.
     *
     * @param string $gatewayCode
     * @param int|null $langId
     * @return string
     */
    private function getPaymentOptionFrontEndNameByGatewayCode(string $gatewayCode, ?int $langId = null): string
    {
        $normalizedGatewayCode = trim($gatewayCode);
        if ($normalizedGatewayCode === '') {
            return $normalizedGatewayCode;
        }

        $paymentOption = $this->paymentOptionService->getMultiSafepayPaymentOption($normalizedGatewayCode);
        if (!$paymentOption) {
            return $normalizedGatewayCode;
        }

        return $paymentOption->getFrontEndName($langId);
    }

    /**
     * Resolve the wallet gateway code from callback payload.
     *
     * Reads `payment_details.wallet` and returns it when present as a non-empty string.
     *
     * @param TransactionResponse $transaction
     * @return string
     */
    private function getWalletGatewayCodeFromTransaction(TransactionResponse $transaction): string
    {
        $paymentDetailsData = $transaction->getPaymentDetails()->getData();
        if (isset($paymentDetailsData['wallet']) && is_string($paymentDetailsData['wallet'])) {
            $walletGatewayCode = trim($paymentDetailsData['wallet']);
            if ($walletGatewayCode !== '') {
                return $walletGatewayCode;
            }
        }

        return '';
    }

    /**
     * Creates TransactionResponse from the POST notification body
     *
     * Parses the JSON notification payload from MultiSafepay and creates a TransactionResponse object.
     *
     * @api
     * @param string $body
     * @return TransactionResponse
     * @throws PrestaShopException
     *
     * @see \MultiSafepay\Tests\Services\NotificationServiceTest
     */
    public function getTransactionFromPostNotification(string $body): TransactionResponse
    {
        try {
            return new TransactionResponse(json_decode($body, true), $body);
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'error',
                $exception
            );
            throw new PrestaShopException($exception->getMessage());
        }
    }

    /**
     * Return the order status id for the given transaction status
     *
     * @param string $transactionStatus
     * @return int
     */
    public function getOrderStatusId(string $transactionStatus): int
    {
        switch ($transactionStatus) {
            case Transaction::CANCELLED:
            case Transaction::EXPIRED:
            case Transaction::VOID:
                $orderStatusId = Configuration::get('PS_OS_CANCELED');
                break;
            case Transaction::DECLINED:
                $orderStatusId = Configuration::get('PS_OS_ERROR');
                break;
            case Transaction::COMPLETED:
                $orderStatusId = Configuration::get('PS_OS_PAYMENT');
                break;
            case Transaction::UNCLEARED:
                $orderStatusId = Configuration::get('MULTISAFEPAY_OFFICIAL_OS_UNCLEARED');
                break;
            case Transaction::REFUNDED:
                $orderStatusId = Configuration::get('PS_OS_REFUND');
                break;
            case Transaction::PARTIAL_REFUNDED:
                $orderStatusId = Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_REFUNDED');
                break;
            case Transaction::CHARGEDBACK:
                $orderStatusId = Configuration::get('MULTISAFEPAY_OFFICIAL_OS_CHARGEBACK');
                break;
            case Transaction::SHIPPED:
                $orderStatusId = Configuration::get('PS_OS_SHIPPING');
                break;
            case 'authorized':
                $orderStatusId = Configuration::get('MULTISAFEPAY_OFFICIAL_OS_AUTHORIZED');
                break;
            case 'partial_captured':
            case 'partially_captured':
                $orderStatusId = Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED');
                break;
            case Transaction::INITIALIZED:
            default:
                $orderStatusId = Configuration::get('MULTISAFEPAY_OFFICIAL_OS_INITIALIZED');
                break;
        }

        // Log the status mapping for debugging
        if (Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE')) {
            LoggerHelper::log(
                'info',
                'Transaction status "' . $transactionStatus . '" mapped to order status ID: ' . $orderStatusId
            );
        }

        return (int)$orderStatusId;
    }

    /**
     * Return if the Order Status is final, therefore, should not be changed anymore.
     *
     * @param int $orderStatus
     * @return bool
     */
    private function isFinalStatus(int $orderStatus): bool
    {
        $finalOrderStatuses = ConfigHelper::settingToIntArray(Configuration::get('MULTISAFEPAY_OFFICIAL_FINAL_ORDER_STATUS'));

        return (in_array($orderStatus, $finalOrderStatuses, true));
    }

    /**
     * @param string $status
     * @param string $transactionType
     * @return bool
     */
    protected function allowOrderCreation(string $status, string $transactionType): bool
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
}
