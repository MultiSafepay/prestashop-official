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

use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Helper\CancelOrderHelper;
use MultiSafepay\PrestaShop\Helper\ConfigHelper;
use MultiSafepay\PrestaShop\Helper\DuplicateCartHelper;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Services\SdkService;
use Psr\Http\Client\ClientExceptionInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class MultisafepayOfficialCancelModuleFrontController extends ModuleFrontController
{
    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function postProcess()
    {
        $cartId = Tools::getValue('id_cart');

        if (empty($this->module->active) || !$cartId) {
            LoggerHelper::log(
                'warning',
                'It seems postProcess method of cancel controller is being called without the required parameters.',
                true,
                null,
                $cartId ?: null
            );
            http_response_code(400);
            die();
        }

        $cart = new Cart($cartId);
        $transaction = null;

        // Check payment status in MultiSafepay before attempting to cancel
        try {
            $sdkService         = new SdkService();
            $transactionManager = $sdkService->getSdk()->getTransactionManager();
            $transaction = $transactionManager->get(Tools::getValue('id_reference'));

            // Validate transaction status - will redirect if not cancellable
            $this->validateTransactionStatusForCancellation($transaction, $cartId);
        } catch (ApiException $apiException) {
            // If we cannot verify the payment status in MultiSafepay, do not cancel for safety
            LoggerHelper::logException(
                'error',
                $apiException,
                'Cannot verify payment status in MultiSafepay. Cancellation aborted for safety',
                null,
                $cart->id ?? null
            );
            Tools::redirect($this->context->link->getPageLink('order', true, null, ['step' => '3']));
        }

        // If an order was created before payment, then we need to duplicate the cart and cancel the order
        if ($cart->orderExists()) {
            // Duplicate cart
            DuplicateCartHelper::duplicateCart($cart, $this->context);

            $orderCollection = new PrestaShopCollection('Order');
            $orderCollection->where('id_cart', '=', $cartId);

            foreach ($orderCollection->getResults() as $order) {
                // Prevent canceling an order with different secure key
                if (!$this->checkOrderSecureKey($order)) {
                    Tools::redirect($this->context->link->getPageLink('order', true, null, ['step' => '3']));
                }

                // Prevent canceling an order if the current order status is not initialized or backorder unpaid
                if (!$this->canOrderBeCancelled($order)) {
                    LoggerHelper::log(
                        'warning',
                        'Order ' . $order->id . ' cannot be cancelled. Current state: ' . $order->current_state,
                        true,
                        (string)$order->id,
                        $cartId
                    );
                    Tools::redirect($this->context->link->getPageLink('order', true, null, ['step' => '3']));
                }
            }

            // Cancel order
            CancelOrderHelper::cancelOrder($orderCollection);
        }

        // Show appropriate error message based on transaction status
        $errorMessage = $this->module->l('Your transaction was declined, please try again', 'cancel');

        // Customize message based on transaction status if available
        if ($transaction !== null) {
            $status = $transaction->getStatus();
            switch ($status) {
                case Transaction::CANCELLED:
                    $errorMessage = $this->module->l('Your transaction was cancelled', 'cancel');
                    break;
                case Transaction::EXPIRED:
                    $errorMessage = $this->module->l('Your transaction has expired, please try again', 'cancel');
                    break;
                case Transaction::VOID:
                    $errorMessage = $this->module->l('Your transaction was voided', 'cancel');
                    break;
                case Transaction::DECLINED:
                default:
                    $errorMessage = $this->module->l('Your transaction was declined, please try again', 'cancel');
                    break;
            }
        }

        $this->context->smarty->assign(
            [
                'layout'         => 'full-width-template',
                'error_message'  => $errorMessage
            ]
        );

        return $this->setTemplate('module:multisafepayofficial/views/templates/front/error.tpl');
    }

    /**
     * Check the secure key of the order with the one received as a query argument
     *
     * @param Order $order
     * @return bool
     */
    private function checkOrderSecureKey(Order $order): bool
    {
        if (Tools::getValue('key') === $order->secure_key) {
            return true;
        }
        return false;
    }

    /**
     * Validate if transaction status allows cancellation
     * Redirects to appropriate page if cancellation is not allowed
     *
     * @param TransactionResponse $transaction
     * @param int $cartId
     * @return void
     */
    private function validateTransactionStatusForCancellation(TransactionResponse $transaction, int $cartId): void
    {
        $transactionStatus = $transaction->getStatus();

        // If the payment is already completed, uncleared, or shipped in MultiSafepay, do not cancel
        $protectedStatuses = [
            Transaction::COMPLETED,
            Transaction::UNCLEARED,
            Transaction::SHIPPED
        ];

        if (in_array($transactionStatus, $protectedStatuses)) {
            LoggerHelper::log(
                'warning',
                'Cancellation attempt blocked for cart ' . $cartId .
                '. Payment already ' . $transactionStatus . ' in MultiSafepay',
                true,
                null,
                $cartId
            );
            // Redirect to order confirmation if payment was successful
            Tools::redirect($this->context->link->getPageLink('order-confirmation', true));
        }

        // Only allow cancellation if the status is explicitly cancellable
        $cancellableStatuses = [
            Transaction::DECLINED,
            Transaction::CANCELLED,
            Transaction::EXPIRED,
            Transaction::VOID
        ];

        if (!in_array($transactionStatus, $cancellableStatuses)) {
            LoggerHelper::log(
                'info',
                'Cancellation redirected for cart ' . $cartId .
                '. Transaction status is ' . $transactionStatus,
                true,
                null,
                $cartId
            );
            Tools::redirect($this->context->link->getPageLink('order', true, null, ['step' => '3']));
        }
    }

    /**
     * Check if the current order status is initialized or backorder unpaid
     * Also checks against final order statuses configured by the merchant
     *
     * @param Order $order
     * @return bool
     */
    private function canOrderBeCancelled(Order $order): bool
    {
        $currentState = (int)$order->current_state;

        // Get final order status IDs configured by merchant using the same method as NotificationService
        $finalOrderStatusSetting = Configuration::get('MULTISAFEPAY_OFFICIAL_FINAL_ORDER_STATUS');
        $finalStatuses = ConfigHelper::settingToIntArray($finalOrderStatusSetting);

        // Do not cancel if order is in a final state (completed, shipped, etc.)
        if (in_array($currentState, $finalStatuses, true)) {
            LoggerHelper::log(
                'warning',
                'Order cannot be cancelled. State ' . $currentState .
                ' is in final order statuses: ' . $finalOrderStatusSetting,
                true,
                (string)$order->id,
                $order->id_cart ?? null
            );
            return false;
        }

        // Also check default PrestaShop paid statuses
        $paidStatuses = [
            (int)Configuration::get('PS_OS_PAYMENT'),        // Payment accepted
            (int)Configuration::get('PS_OS_WS_PAYMENT'),     // Remote payment accepted
            (int)Configuration::get('PS_OS_DELIVERED'),      // Delivered
            (int)Configuration::get('PS_OS_SHIPPING'),       // Shipped
            (int)Configuration::get('PS_OS_PREPARATION'),    // Preparation in progress
        ];

        // Do not cancel if order is already paid or in processing
        if (in_array($currentState, $paidStatuses)) {
            LoggerHelper::log(
                'warning',
                'Order cannot be cancelled. State ' . $currentState . ' indicates payment was accepted',
                true,
                (string)$order->id,
                $order->id_cart ?? null
            );
            return false;
        }

        // Only allow cancellation if order is in initialized or backorder unpaid status
        $cancellableStatuses = [
            (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_INITIALIZED'),
            (int)Configuration::get('PS_OS_OUTOFSTOCK_UNPAID')
        ];

        return in_array($currentState, $cancellableStatuses);
    }
}
