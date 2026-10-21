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

use Configuration;
use Currency;
use Exception;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Exception\ApiException;
use Order;
use OrderState;
use PrestaShopException;
use Psr\Http\Client\ClientExceptionInterface;
use Tools;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Helper class for handling manual capture operations
 *
 * This class encapsulates all manual capture-related functionality including
 * - Detection of manual capture transactions
 * - Capture execution and validation
 * - Status management and verification
 * - Invoice processing for captured orders
 */
class ManualCaptureHelper
{
    /**
     * Validate configuration value to ensure it is a valid positive integer
     *
     * @param mixed $configValue The configuration value to validate
     * @return int|null Returns the integer value if valid, null otherwise
     */
    private static function validateConfigValue($configValue): ?int
    {
        // Handle null, false, empty string cases
        if ($configValue === false || $configValue === null || $configValue === '') {
            return null;
        }

        // Convert to string and trim whitespace
        $valueStr = trim((string)$configValue);

        // Check if the string is empty after trimming
        if ($valueStr === '') {
            return null;
        }

        // Check if it contains only digits (no negative, no decimals, no other chars)
        if (!ctype_digit($valueStr)) {
            return null;
        }

        $intValue = (int)$valueStr;

        // Ensure it is a positive integer (greater than 0)
        // NOTE: PrestaShop method: assertIntegerIsGreaterThanZero()
        // at OrderStateId.php prevents that state ID be 0
        if ($intValue <= 0) {
            return null;
        }

        return $intValue;
    }

    /**
     * Read required capture configuration without repairing it during a request.
     *
     * @param string $configKey
     * @return int
     * @throws PrestaShopException
     */
    public static function getRequiredOrderStateId(string $configKey): int
    {
        $statusId = self::validateConfigValue(Configuration::get($configKey));
        if ($statusId !== null) {
            $orderState = new OrderState($statusId);
            if (Validate::isLoadedObject($orderState) && !$orderState->deleted) {
                return $statusId;
            }
        }

        $message = sprintf(
            'MultiSafepay order state "%s" is missing or invalid. Complete the module upgrade '
            . 'from the Module manager. If the upgrade already completed, contact support.',
            $configKey
        );
        LoggerHelper::log('error', $message);
        throw new PrestaShopException($message);
    }

    /**
     * Check if a transaction is a manual capture transaction
     *
     * @param TransactionResponse $transaction The MultiSafepay transaction object
     * @return bool True if it's a manual capture transaction
     */
    public static function isManualCaptureTransaction(TransactionResponse $transaction): bool
    {
        return $transaction->getPaymentDetails()->getCapture() === CaptureRequest::CAPTURE_MANUAL_TYPE;
    }

    /**
     * Check if the transaction should be set to "authorized" status
     *
     * Returns true when:
     * - status is "completed"
     * - financial_status is "initialized"
     *
     * @param TransactionResponse $transaction The MultiSafepay transaction object
     * @return bool True if the order should be marked as authorized
     */
    public static function shouldBeAuthorizedStatus(TransactionResponse $transaction): bool
    {
        if ($transaction->getStatus() !== Transaction::COMPLETED
            || $transaction->getFinancialStatus() !== Transaction::INITIALIZED) {
            return false;
        }

        // If a partial capture is detected, this notification should not be treated as authorized.
        if (self::shouldBePartiallyCapturedStatus($transaction)) {
            return false;
        }

        $amount = $transaction->getAmount();
        $captureRemain = self::getCaptureRemainFromTransactionData($transaction);

        // Backward-compatible fallback when capture_remain is not present in the payload.
        if ($amount <= 0 || $captureRemain === null) {
            return true;
        }

        return $captureRemain >= $amount;
    }

    /**
     * Check if transaction should be set to "payment accepted" status
     *
     * Returns true when:
     * - status is "completed"
     * - financial_status is "completed"
     * - OR manual capture callback indicates the remaining amount is 0
     *
     * @param TransactionResponse $transaction The MultiSafepay transaction object
     * @return bool True if the order should be marked as payment accepted
     */
    public static function shouldBePaymentAcceptedStatus(TransactionResponse $transaction): bool
    {
        if ($transaction->getStatus() !== Transaction::COMPLETED) {
            return false;
        }

        if ($transaction->getFinancialStatus() === Transaction::COMPLETED) {
            return true;
        }

        if (!self::isManualCaptureTransaction($transaction)) {
            return false;
        }

        $captureRemain = self::getCaptureRemainFromTransactionData($transaction);

        return $captureRemain !== null && $captureRemain <= 0;
    }

    /**
     * Check if transaction should be set to "partially captured" status
     *
     * Returns true when:
     * - status is "completed"
     * - financial_status indicates a partial capture
     *
     * @param TransactionResponse $transaction The MultiSafepay transaction object
     * @return bool True if the order should be marked as partially captured
     */
    public static function shouldBePartiallyCapturedStatus(TransactionResponse $transaction): bool
    {
        if ($transaction->getStatus() !== Transaction::COMPLETED) {
            return false;
        }

        $captureType = Tools::strtolower($transaction->getPaymentDetails()->getCapture());
        if ($captureType !== CaptureRequest::CAPTURE_MANUAL_TYPE) {
            return false;
        }

        $amount = $transaction->getAmount();
        $captureRemain = self::getCaptureRemainFromTransactionData($transaction);

        if ($amount > 0 && $captureRemain !== null) {
            return $captureRemain > 0 && $captureRemain < $amount;
        }

        // Fallback for callback payloads where capture_remain is not present
        return self::hasCaptureRelatedTransaction($transaction);
    }

    /**
     * Extract capture remaining amount from callback payload.
     *
     * @param TransactionResponse $transaction
     * @return int|null
     */
    private static function getCaptureRemainFromTransactionData(TransactionResponse $transaction): ?int
    {
        $transactionData = $transaction->getData();
        if ($transactionData === []
            || !isset($transactionData['payment_details'])
            || !is_array($transactionData['payment_details'])
            || !array_key_exists('capture_remain', $transactionData['payment_details'])) {
            return null;
        }

        return (int)$transactionData['payment_details']['capture_remain'];
    }

    /**
     * Determine if callback payload includes a capture related transaction.
     *
     * @param TransactionResponse $transaction
     * @return bool
     */
    private static function hasCaptureRelatedTransaction(TransactionResponse $transaction): bool
    {
        $transactionData = $transaction->getData();
        if ($transactionData === []
            || !isset($transactionData['related_transactions'])
            || !is_array($transactionData['related_transactions'])) {
            return false;
        }

        foreach ($transactionData['related_transactions'] as $relatedTransaction) {
            if (!is_array($relatedTransaction) || !isset($relatedTransaction['type'])) {
                continue;
            }

            if (Tools::strtolower((string)$relatedTransaction['type']) === 'capture') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a transaction status allows cancellation
     *
     * @param TransactionResponse $transaction The MultiSafepay transaction object
     * @return bool True if the transaction status allows cancellation
     */
    public static function isCancellableStatus(TransactionResponse $transaction): bool
    {
        $currentStatus = $transaction->getStatus();
        return in_array($currentStatus, [Transaction::COMPLETED, Transaction::UNCLEARED], true);
    }

    /**
     * Check if a transaction has already been captured by verifying API status
     *
     * This is more reliable than checking local PrestaShop status due to state mapping differences
     *
     * @param TransactionManager $transactionManager
     * @param string $orderId
     * @return bool True if already captured or not capturable
     * @throws ClientExceptionInterface
     */
    public static function isAlreadyCaptured(TransactionManager $transactionManager, string $orderId): bool
    {
        try {
            $currentTransaction = $transactionManager->get($orderId);
            $apiStatus = $currentTransaction->getStatus();

            // If API status is not 'completed', the capture was already done or not capturable
            return $apiStatus !== Transaction::COMPLETED;
        } catch (ApiException $apiException) {
            // If we cannot check status, assume it not captured to avoid blocking
            return false;
        }
    }

    /**
     * Validate that transaction amount matches order total
     *
     * @param Order $order The order object
     * @param TransactionManager $transactionManager MultiSafepay transaction manager
     * @param string $orderId Order ID
     *
     * @throws Exception If amounts do not match
     * @throws ClientExceptionInterface
     */
    public static function validateTransactionAmount(
        Order $order,
        TransactionManager $transactionManager,
        string $orderId
    ): void {
        $orderIdForLog = (string)$order->id ?: null;
        $cartIdForLog = $order->id_cart ?: null;

        try {
            $transaction = $transactionManager->get($orderId);
            $transactionAmount = (float)$transaction->getAmount() / 100;
            $orderAmount = (float)$order->total_paid;
            $transactionCurrency = $transaction->getCurrency() ?? 'n/a';
            $orderCurrency = $order->id_currency ?
                (Currency::getCurrency((int)$order->id_currency)['iso_code'] ?? 'n/a') : 'n/a';

            // Validate that we have valid currency information
            if ($transactionCurrency === 'n/a' || $orderCurrency === 'n/a') {
                $errorMessage = sprintf(
                    'Cannot validate amounts: Missing currency information. '
                    . "'Transaction' currency: %s, 'Order' currency: %s",
                    $transactionCurrency,
                    $orderCurrency
                );

                LoggerHelper::log(
                    'error',
                    $errorMessage,
                    false,
                    $orderIdForLog,
                    $cartIdForLog
                );

                throw new Exception($errorMessage);
            }

            // Validate that we have valid amount values
            if ($transactionAmount <= 0 || $orderAmount <= 0) {
                $errorMessage = sprintf(
                    'Cannot validate amounts: Invalid amount values. '
                    . "'Transaction': %s %s, 'Order': %s %s",
                    $transactionAmount,
                    $transactionCurrency,
                    $orderAmount,
                    $orderCurrency
                );

                LoggerHelper::log(
                    'error',
                    $errorMessage,
                    false,
                    $orderIdForLog,
                    $cartIdForLog
                );

                throw new Exception($errorMessage);
            }

            // Validate that currencies match
            if ($transactionCurrency !== $orderCurrency) {
                $errorMessage = sprintf(
                    'Cannot validate amounts: Currency mismatch detected. '
                    . "'Transaction' currency: %s, 'Order' currency: %s. "
                    . 'Amounts cannot be compared directly without currency conversion.',
                    $transactionCurrency,
                    $orderCurrency
                );

                LoggerHelper::log(
                    'error',
                    $errorMessage,
                    false,
                    $orderIdForLog,
                    $cartIdForLog
                );

                throw new Exception($errorMessage);
            }

            // Perform the actual amount comparison
            if ($transactionAmount !== $orderAmount) {
                $errorMessage = sprintf(
                    'Amount mismatch in the manual capture process. '
                    . "%s %s from 'Transaction' is not equal to %s %s from 'Order'",
                    $transactionAmount,
                    $transactionCurrency,
                    $orderAmount,
                    $orderCurrency
                );

                LoggerHelper::log(
                    'error',
                    $errorMessage,
                    false,
                    $orderIdForLog,
                    $cartIdForLog
                );

                throw new Exception($errorMessage);
            }

            // All validations passed successfully
            LoggerHelper::log(
                'info',
                sprintf(
                    'Amount validation passed in the manual capture process. '
                    . "%s %s from 'Transaction' is equal to %s %s from 'Order'",
                    $transactionAmount,
                    $transactionCurrency,
                    $orderAmount,
                    $orderCurrency
                ),
                false,
                $orderIdForLog,
                $cartIdForLog
            );
        } catch (ApiException $apiException) {
            LoggerHelper::logException(
                'error',
                $apiException,
                'Failed to retrieve transaction for amount validation in the manual capture process',
                $orderIdForLog,
                $cartIdForLog
            );
            throw $apiException;
        }
    }

    /**
     * Execute capture for manual capture transaction
     *
     * @param Order $order The order object
     * @param TransactionManager $transactionManager MultiSafepay transaction manager
     * @param string $orderId Order ID
     * @param string $trackingNumber Order tracking number
     * @param string $carrierName Carrier name
     *
     * @throws Exception If capture fails
     * @throws ClientExceptionInterface
     * @throws PrestaShopException
     */
    public static function captureTransactionFunds(
        Order $order,
        TransactionManager $transactionManager,
        string $orderId,
        string $trackingNumber = '',
        string $carrierName = ''
    ): void {
        // Refresh the order object to get the most current status
        $order = new Order($order->id);
        $orderIdForLog = (string)$order->id ?: null;
        $cartIdForLog = $order->id_cart ?: null;

        // Validate transaction amount matches order total before proceeding
        self::validateTransactionAmount($order, $transactionManager, $orderId);

        // Check if already captured using API status verification
        if (self::isAlreadyCaptured($transactionManager, $orderId)) {
            LoggerHelper::log(
                'info',
                'Capture skipped - already captured or not capturable',
                false,
                $orderIdForLog,
                $cartIdForLog
            );
            return;
        }

        $captureRequest = new CaptureRequest();
        $captureRequest->addData([
            'amount' => (int)round((float)$order->total_paid * 100),
            'new_order_status' => Transaction::COMPLETED,
            'tracktrace_code' => $trackingNumber,
            'carrier' => $carrierName,
            'reason' => 'Order shipped in PrestaShop' . (defined('_PS_VERSION_') ? ' ' . _PS_VERSION_ : ''),
            'description' => 'Manual capture process for order #' . $order->id
        ]);

        try {
            $captureResponse = $transactionManager->capture($orderId, $captureRequest);
            $responseCaptureRawData = json_decode($captureResponse->getRawData(), false);
            $responseCaptureData = $captureResponse->getResponseData();

            if (!empty($responseCaptureRawData->success)) {
                LoggerHelper::log(
                    'info',
                    "Funds were captured successfully when status was changed to 'shipped'. Transaction ID = " .
                    ($responseCaptureData['transaction_id'] ?? 'n/a') .
                    '. PrestaShop order ID = ' . ($responseCaptureData['order_id'] ?? 'n/a'),
                    false,
                    $orderIdForLog,
                    $cartIdForLog
                );

                LoggerHelper::log(
                    'info',
                    'Manual capture completed successfully',
                    false,
                    $orderIdForLog,
                    $cartIdForLog
                );
            } else {
                LoggerHelper::log(
                    'alert',
                    'Manual funds capture failed. API response: ' . json_encode($responseCaptureRawData),
                    false,
                    $orderIdForLog,
                    $cartIdForLog
                );

                // Throw exception to let the caller handle the failure
                throw new Exception('Manual capture failed: ' . json_encode($responseCaptureRawData));
            }
        } catch (ApiException $apiException) {
            if ($apiException->getMessage() === 'fullcaptured') {
                LoggerHelper::logException(
                    'info',
                    $apiException,
                    "Funds already captured manually when status was changed to 'shipped' previously.",
                    $orderIdForLog,
                    $cartIdForLog
                );
            } else {
                LoggerHelper::logException(
                    'alert',
                    $apiException,
                    'API error when manually capturing funds',
                    $orderIdForLog,
                    $cartIdForLog
                );

                // Re-throw to let the caller handle the exception
                throw $apiException;
            }
        }
    }

    /**
     * Add an order note for manual capture authorization
     *
     * @param Order $order The order object
     * @param TransactionResponse $transaction The transaction response
     * @return void
     */
    public static function addManualCaptureNote(Order $order, TransactionResponse $transaction): void
    {
        $orderIdForLog = (string)$order->id ?: null;
        $cartIdForLog = $order->id_cart ?: null;

        // Get capture expiry from transaction data
        $captureExpiry = null;
        $transactionData = $transaction->getData();
        if (isset($transactionData['payment_details']['capture_expiry'])) {
            $captureExpiry = $transactionData['payment_details']['capture_expiry'];
        }

        // Since partial shipments per order are not supported,
        // we count all items from the purchase
        $orderDetailList = $order->getOrderDetailList();
        $totalItems = 0;
        foreach ($orderDetailList as $item) {
            $totalItems += (int)$item['product_quantity'];
        }

        if ($totalItems > 0) {
            $itemText = $totalItems > 1 ? $totalItems . ' items' : 'item';

            if ($captureExpiry) {
                $expiryDate = date('Y-m-d H:i:s', strtotime($captureExpiry));
                $message = sprintf(
                    'Payment has been authorized and will expire on %s. To complete the capture, the '
                    . $itemText . ' should be shipped before this date.',
                    $expiryDate
                );
            } else {
                $message = 'Payment has been authorized and will be captured once the ' . $itemText . ' is shipped.';
            }
        } else {
            $message = 'Payment has been authorized.';
        }

        try {
            OrderMessageHelper::addMessage($order, $message);
            LoggerHelper::log(
                'info',
                'Manual capture note added for order #' . $order->id . ': ' . $message,
                true,
                $orderIdForLog,
                $cartIdForLog
            );
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'warning',
                $exception,
                'Failed to add manual capture note',
                $orderIdForLog,
                $cartIdForLog
            );
        }
    }

    /**
     * Add an order note when a manual capture payment has been captured (final step)
     *
     * @param Order $order The order object
     * @param TransactionResponse $transaction The transaction response
     * @return void
     */
    public static function addFinishedManualCaptureNote(Order $order, TransactionResponse $transaction): void
    {
        $orderIdForLog = (string)$order->id ?: null;
        $cartIdForLog = $order->id_cart ?: null;

        // Get transaction information for a more detailed message
        $transactionId = $transaction->getTransactionId() ?? 'n/a';
        $currency = $order->id_currency ?
            (Currency::getCurrency((int)$order->id_currency)['iso_code'] ?? 'n/a') :
            (
                Configuration::get('PS_CURRENCY_DEFAULT') ?
                (
                    Currency::getCurrency((int)Configuration::get('PS_CURRENCY_DEFAULT'))['iso_code'] ?? 'n/a'
                ) : 'n/a'
            );
        $capturedAmountInCents = $transaction->getAmount();
        $capturedAmount = $capturedAmountInCents > 0
            ? number_format($capturedAmountInCents / 100, 2)
            : 'n/a';

        $captureAmounts = self::getCaptureAmountsFromRelatedTransactions($transaction);
        if (count($captureAmounts) > 1) {
            $lastPartialAmountInCents = (int)$captureAmounts[count($captureAmounts) - 1];
            $lastPartialAmount = number_format($lastPartialAmountInCents / 100, 2);

            $message = sprintf(
                'Capture has been successfully completed through multiple partial captures. '
                . 'Last partial amount: %s %s. Total amount: %s %s. Transaction ID: %s.',
                $lastPartialAmount,
                $currency,
                $capturedAmount,
                $currency,
                $transactionId
            );
        } else {
            $message = sprintf(
                'Payment has been successfully captured. Amount: %s %s. Transaction ID: %s.',
                $capturedAmount,
                $currency,
                $transactionId
            );
        }

        try {
            OrderMessageHelper::addMessage($order, $message);
            LoggerHelper::log(
                'info',
                'Manual capture completed note added. ' . $message,
                true,
                $orderIdForLog,
                $cartIdForLog
            );
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'warning',
                $exception,
                'Failed to add manual capture completed note',
                $orderIdForLog,
                $cartIdForLog
            );
        }
    }

    /**
     * Extract all capture amounts from callback-related transactions.
     *
     * @param TransactionResponse $transaction
     * @return int[]
     */
    private static function getCaptureAmountsFromRelatedTransactions(TransactionResponse $transaction): array
    {
        $transactionData = $transaction->getData();
        if ($transactionData === []
            || !isset($transactionData['related_transactions'])
            || !is_array($transactionData['related_transactions'])) {
            return [];
        }

        $captureAmounts = [];
        foreach ($transactionData['related_transactions'] as $relatedTransaction) {
            if (!is_array($relatedTransaction) || !isset($relatedTransaction['type'])) {
                continue;
            }

            if (Tools::strtolower((string)$relatedTransaction['type']) !== 'capture') {
                continue;
            }

            if (!array_key_exists('amount', $relatedTransaction)) {
                continue;
            }

            $captureAmount = (int)$relatedTransaction['amount'];
            if ($captureAmount > 0) {
                $captureAmounts[] = $captureAmount;
            }
        }

        return $captureAmounts;
    }

    /**
     * Add an order note when a manual capture payment has been partially captured
     *
     * @param Order $order The order object
     * @param TransactionResponse $transaction The transaction response
     * @return void
     */
    public static function addPartialManualCaptureNote(Order $order, TransactionResponse $transaction): void
    {
        $orderIdForLog = (string)$order->id ?: null;
        $cartIdForLog = $order->id_cart ?: null;

        $transactionId = $transaction->getTransactionId() ?: 'n/a';
        $currency = $order->id_currency ?
            (Currency::getCurrency((int)$order->id_currency)['iso_code'] ?? 'n/a') :
            (
                Configuration::get('PS_CURRENCY_DEFAULT') ?
                (
                    Currency::getCurrency((int)Configuration::get('PS_CURRENCY_DEFAULT'))['iso_code'] ?? 'n/a'
                ) : 'n/a'
            );
        $capturedAmountInCents = self::getCapturedAmountForManualCapturePaymentInCents($transaction);
        $capturedAmount = $capturedAmountInCents !== null
            ? number_format($capturedAmountInCents / 100, 2)
            : 'n/a';

        $message = sprintf(
            'Payment has been partially captured successfully. Amount: %s %s. Transaction ID: %s.',
            $capturedAmount,
            $currency,
            $transactionId
        );

        try {
            OrderMessageHelper::addMessage($order, $message);
            LoggerHelper::log(
                'info',
                'Manual partial capture note added. ' . $message,
                true,
                $orderIdForLog,
                $cartIdForLog
            );
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'warning',
                $exception,
                'Failed to add manual partial capture note',
                $orderIdForLog,
                $cartIdForLog
            );
        }
    }

    /**
     * Resolve captured amount for manual-capture payment row registration.
     *
     * @param TransactionResponse $transaction
     * @return int|null
     */
    public static function getCapturedAmountForManualCapturePaymentInCents(TransactionResponse $transaction): ?int
    {
        return self::getPartialCapturedAmountInCents($transaction);
    }

    /**
     * Extract a captured partial amount (in cents) from the callback payload.
     *
     * Priority:
     * 1) Last related transaction of type capture with amount
     * 2) payment_details.capture_amount
     * 3) payment_details.amount_captured
     * 4) No signal available => return null
     *
     * @param TransactionResponse $transaction
     * @return int|null
     */
    private static function getPartialCapturedAmountInCents(TransactionResponse $transaction): ?int
    {
        $transactionData = $transaction->getData();
        if ($transactionData === []) {
            LoggerHelper::log(
                'warning',
                'Manual capture: callback payload is empty; ' .
                'captured amount cannot be resolved for payment row registration.'
            );

            return null;
        }

        $captureAmounts = self::getCaptureAmountsFromRelatedTransactions($transaction);
        if (!empty($captureAmounts)) {
            return (int)$captureAmounts[count($captureAmounts) - 1];
        }

        if (isset($transactionData['payment_details']) && is_array($transactionData['payment_details'])) {
            $paymentDetails = $transactionData['payment_details'];

            if (array_key_exists('capture_amount', $paymentDetails)) {
                $captureAmount = (int)$paymentDetails['capture_amount'];
                if ($captureAmount > 0) {
                    return $captureAmount;
                }
            }

            if (array_key_exists('amount_captured', $paymentDetails)) {
                $amountCaptured = (int)$paymentDetails['amount_captured'];
                if ($amountCaptured > 0) {
                    return $amountCaptured;
                }
            }
        }

        LoggerHelper::log(
            'warning',
            'Manual capture: no capture amount signal found in callback payload; skipping payment row registration.'
        );

        return null;
    }
}
