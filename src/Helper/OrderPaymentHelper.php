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

use Db;
use MultiSafepay\Api\Transactions\TransactionResponse;
use Order;
use OrderInvoice;
use OrderPayment;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tools;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class OrderPaymentHelper
 *
 * Helper class for managing OrderPayment data related to MultiSafepay transactions
 *
 * @package MultiSafepay\PrestaShop\Helper
 */
class OrderPaymentHelper
{
    /**
     * Sync Order Payment with Transaction Information
     *
     * Updates existing OrderPayment records with MultiSafepay transaction data
     * or creates a new payment record when none exists.
     *
     * @param Order $order
     * @param TransactionResponse $transaction
     * @return void
     * @throws PrestaShopException
     */
    public static function syncOrderPaymentWithTransaction(Order $order, TransactionResponse $transaction): void
    {
        $payments = $order->getOrderPaymentCollection();
        if ($payments->count() > 0) {
            self::updateOrderPaymentWithTransactionInfo($order, $transaction);
            return;
        }

        self::createOrderPaymentWithTransactionInfo($order, $transaction);
    }

    /**
     * Updates Order Payment Details with Transaction Information
     *
     * Synchronizes PrestaShop payment records with MultiSafepay transaction data,
     * updating transaction ID, amount, and payment method for consistency.
     *
     * @param Order $order
     * @param TransactionResponse $transaction
     * @throws PrestaShopException
     */
    public static function updateOrderPaymentWithTransactionInfo(Order $order, TransactionResponse $transaction): void
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
     * Creates Order Payment with Transaction Information
     *
     * Creates a new OrderPayment record with MultiSafepay transaction data.
     * This method should be called when no payment exists for the order.
     *
     * @param Order $order
     * @param TransactionResponse $transaction
     * @throws PrestaShopException
     */
    public static function createOrderPaymentWithTransactionInfo(Order $order, TransactionResponse $transaction): void
    {
        $orderIdForLog = (string)$order->id ?: null;
        $cartIdForLog = $order->id_cart ?: null;

        $payment = new OrderPayment();
        $payment->order_reference = Tools::substr($order->reference, 0, 9);
        $payment->id_currency = $order->id_currency;
        $payment->amount = $transaction->getAmount() / 100;
        $payment->payment_method = $order->payment;
        $payment->conversion_rate = $order->conversion_rate;
        $payment->transaction_id = $transaction->getTransactionId();

        try {
            $payment->save();

            LoggerHelper::log(
                'info',
                'Payment created for completed order with amount ' . ($transaction->getAmount() / 100) .
                ' and transaction ID: ' . $transaction->getTransactionId(),
                false,
                $orderIdForLog,
                $cartIdForLog
            );
        } catch (PrestaShopException $prestaShopException) {
            LoggerHelper::logException(
                'alert',
                $prestaShopException,
                'Error creating payment for completed order',
                $orderIdForLog,
                $cartIdForLog
            );
            throw $prestaShopException;
        }
    }

    /**
     * Creates a new OrderPayment record with a custom amount.
     *
     * This method is intended for manual-capture callbacks where each callback
     * should append an individual payment row (partial amount) instead of
     * updating existing payment rows.
     *
     * @param Order $order
     * @param TransactionResponse $transaction
     * @param float $amount
     * @param bool $linkToLatestOrderInvoice
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function createOrderPaymentWithCustomAmount(
        Order $order,
        TransactionResponse $transaction,
        float $amount,
        bool $linkToLatestOrderInvoice = false
    ): void {
        $orderIdForLog = (string)$order->id ?: null;
        $cartIdForLog = $order->id_cart ?: null;

        if ($amount <= 0) {
            LoggerHelper::log(
                'warning',
                'Skipped payment creation because amount is not positive: ' . $amount,
                false,
                $orderIdForLog,
                $cartIdForLog
            );
            return;
        }

        $payment = new OrderPayment();
        $payment->order_reference = Tools::substr($order->reference, 0, 9);
        $payment->id_currency = $order->id_currency;
        $payment->amount = $amount;
        $payment->payment_method = $order->payment;
        $payment->conversion_rate = $order->conversion_rate;
        $payment->transaction_id = $transaction->getTransactionId();

        try {
            $payment->save();
            if ($linkToLatestOrderInvoice) {
                self::linkPaymentToLatestOrderInvoice($order, (int)$payment->id);
            }

            LoggerHelper::log(
                'info',
                'Payment created for manual capture with amount ' . $amount
                . ' and transaction ID: ' . $transaction->getTransactionId(),
                false,
                $orderIdForLog,
                $cartIdForLog
            );
        } catch (PrestaShopException $prestaShopException) {
            LoggerHelper::logException(
                'alert',
                $prestaShopException,
                'Error creating payment for manual capture callback',
                $orderIdForLog,
                $cartIdForLog
            );
            throw $prestaShopException;
        }
    }

    /**
     * Link payment to the latest order invoice.
     *
     * @param Order $order
     * @param int $paymentId
     * @return void
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    private static function linkPaymentToLatestOrderInvoice(Order $order, int $paymentId): void
    {
        if ((int)$order->id <= 0 || $paymentId <= 0) {
            return;
        }

        $latestInvoiceId = self::getLatestOrderInvoiceId($order);
        if ($latestInvoiceId <= 0) {
            return;
        }

        $payment = new OrderPayment($paymentId);
        if (!Validate::isLoadedObject($payment)) {
            return;
        }

        $linkedInvoice = $payment->getOrderInvoice((int)$order->id);
        if ($linkedInvoice && Validate::isLoadedObject($linkedInvoice)) {
            return;
        }

        Db::getInstance()->insert(
            'order_invoice_payment',
            [
                'id_order_invoice' => $latestInvoiceId,
                'id_order_payment' => $paymentId,
                'id_order' => (int)$order->id,
            ]
        );
    }

    /**
     * Resolve the latest invoice id for an order using PrestaShop collections.
     *
     * @param Order $order
     * @return int
     */
    private static function getLatestOrderInvoiceId(Order $order): int
    {
        $latestInvoiceId = 0;
        $latestInvoiceDateAdd = '';

        /** @var OrderInvoice $invoice */
        foreach ($order->getInvoicesCollection() as $invoice) {
            $invoiceId = (int)$invoice->id;
            if ($invoiceId <= 0) {
                continue;
            }

            $invoiceDateAdd = (string)$invoice->date_add;
            $isMoreRecent = $invoiceDateAdd > $latestInvoiceDateAdd;
            $isSameDateAndHigherId = $invoiceDateAdd === $latestInvoiceDateAdd && $invoiceId > $latestInvoiceId;

            if ($isMoreRecent || $isSameDateAndHigherId) {
                $latestInvoiceId = $invoiceId;
                $latestInvoiceDateAdd = $invoiceDateAdd;
            }
        }

        return $latestInvoiceId;
    }
}
