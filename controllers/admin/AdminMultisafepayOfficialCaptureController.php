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

use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Helper\ManualCaptureHelper;
use MultiSafepay\PrestaShop\Helper\OrderPaymentHelper;
use MultiSafepay\PrestaShop\Services\SdkService;
use Psr\Http\Client\ClientExceptionInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Admin Controller for Manual Capture of MultiSafepay transactions
 *
 * This controller handles the capture of funds for orders that are in "authorized" status.
 * It is triggered from the order detail page when the merchant clicks the "Capture" button.
 */
class AdminMultisafepayOfficialCaptureController extends ModuleAdminController
{
    /**
     * Process the capture request
     *
     * @return void
     * @throws PrestaShopException
     * @throws ClientExceptionInterface
     */
    public function postProcess()
    {
        $orderId = (int)Tools::getValue('id_order');

        if (!$orderId) {
            $this->errors[] = $this->module->l('Invalid order ID');
            $this->redirectToOrdersList();
            return;
        }

        $order = new Order($orderId);

        // Validate order belongs to MultiSafepay
        if (!$order->id || $order->module !== 'multisafepayofficial') {
            $this->errors[] = $this->module->l('This order was not processed by MultiSafepay');
            $this->redirectToOrder($orderId);
            return;
        }

        // Validate order is in authorized status
        $authorizedStatusId = (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_AUTHORIZED');
        if ((int)$order->current_state !== $authorizedStatusId) {
            $this->errors[] = $this->module->l('This order is not in authorized status');
            $this->redirectToOrder($orderId);
            return;
        }

        try {
            $wasUpdated = $this->captureOrderFunds($order);
            if ($wasUpdated) {
                $this->confirmations[] = $this->module->l('Funds have been successfully captured');
            } else {
                $this->confirmations[] = $this->module->l('Capture request sent successfully');
            }
        } catch (Exception $exception) {
            $this->errors[] = sprintf(
                $this->module->l('Error capturing funds: %s'),
                $exception->getMessage()
            );
            LoggerHelper::logException(
                'error',
                $exception,
                'Error during manual capture from admin',
                (string)$order->id,
                $order->id_cart
            );
        }

        $this->redirectToOrder($orderId);
    }

    /**
     * Capture funds for the given order
     *
     * @param Order $order
     * @return void
     * @throws Exception
     * @throws ClientExceptionInterface
     */
    private function captureOrderFunds(Order $order): bool
    {
        $sdkService = new SdkService();
        $sdk = $sdkService->getSdk();

        if (!$sdk) {
            throw new PrestaShopException($this->module->l('Could not initialize MultiSafepay SDK'));
        }

        $transactionManager = $sdk->getTransactionManager();

        // Get transaction ID based on configuration
        $transactionId = Configuration::get('MULTISAFEPAY_OFFICIAL_CREATE_ORDER_BEFORE_PAYMENT') ?
            $order->reference : (string)$order->id_cart;

        // Validate the transaction exists and is capturable
        $transaction = $transactionManager->get($transactionId);

        if (!ManualCaptureHelper::isManualCaptureTransaction($transaction)) {
            throw new PrestaShopException($this->module->l('This transaction does not support manual capture'));
        }

        // Check if already captured
        if (ManualCaptureHelper::isAlreadyCaptured($transactionManager, $transactionId)) {
            throw new PrestaShopException($this->module->l('This transaction has already been captured'));
        }

        // Validate amounts match
        ManualCaptureHelper::validateTransactionAmount($order, $transactionManager, $transactionId);

        // Get tracking information if available
        $trackingNumber = $order->getWsShippingNumber() ?? '';
        $carrierName = '';
        if ($order->id_carrier) {
            $carrier = new Carrier((int)$order->id_carrier);
            $carrierName = $carrier->name ?: '';
        }

        // Perform the capture
        ManualCaptureHelper::captureTransactionFunds(
            $order,
            $transactionManager,
            $transactionId,
            $trackingNumber,
            $carrierName
        );

        // Try to sync the order immediately so the admin sees changes without refreshing.
        return $this->tryUpdateOrderAfterCapture($order, $transactionManager, $transactionId);
    }

    /**
     * Best-effort: refresh the transaction status and update the order status/payments.
     * Manual capture can be asynchronous; we retry briefly to reduce the need for a manual refresh.
     *
     * @param Order $order
     * @param mixed $transactionManager
     * @param string $transactionId
     * @return bool True when order status was updated to Payment Accepted
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function tryUpdateOrderAfterCapture(Order $order, $transactionManager, string $transactionId): bool
    {
        // Short retry loop to allow API to reflect the updated financial status.
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $transaction = $transactionManager->get($transactionId);

            if (ManualCaptureHelper::shouldBePaymentAcceptedStatus($transaction)) {
                break;
            }

            // Small delay before next attempt
            if ($attempt < 2) {
                usleep(800000); // 0.8s
            }
        }

        if (!$transaction || !ManualCaptureHelper::shouldBePaymentAcceptedStatus($transaction)) {
            return false;
        }

        $acceptedPaymentId = (int)Configuration::get('PS_OS_PAYMENT');
        $order = new Order((int)$order->id);

        // If already updated, avoid duplicating order history entries.
        if ((int)$order->current_state === $acceptedPaymentId) {
            return true;
        }

        $history = new OrderHistory();
        $history->id_order = (int)$order->id;
        $history->changeIdOrderState($acceptedPaymentId, (int)$order->id, true);
        $history->id_order_state = $acceptedPaymentId;
        $history->addWithemail();

        // Refresh order to get any payments created by changeIdOrderState
        $order = new Order((int)$order->id);

        OrderPaymentHelper::syncOrderPaymentWithTransaction($order, $transaction);

        ManualCaptureHelper::addFinishedManualCaptureNote($order, $transaction);

        return true;
    }

    /**
     * Redirect to the order detail page
     *
     * @param int $orderId
     * @return void
     * @throws PrestaShopException
     */
    private function redirectToOrder(int $orderId): void
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink(
                'AdminOrders',
                true,
                [],
                [
                    'vieworder' => 1,
                    'id_order' => $orderId,
                ]
            )
        );
    }

    /**
     * Redirect to the orders list page
     *
     * @return void
     * @throws PrestaShopException
     */
    private function redirectToOrdersList(): void
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminOrders')
        );
    }
}
