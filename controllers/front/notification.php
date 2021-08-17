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

use MultiSafepay\PrestaShop\Services\SdkService;
use Order as PrestaShopOrder;
use OrderHistory as PrestaShopOrderHistory;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;

class MultisafepayNotificationModuleFrontController extends ModuleFrontController
{

    /**
     * Process notification
     *
     * @todo If the payment method changed in MultiSafepay payment page, after leave WooCommerce checkout page
     * @todo Check if the WooCommerce Order status do not match with the order status received in notification, to avoid to process repeated of notification.
     * @todo What to do with final order statuses: refunded, partial refunded.
     * @todo Register Payments within the order information.
     * @todo Change to support POST notification
     *
     * @return string
     */
    public function postProcess(): string
    {
        if ($this->module->active == false) {
            die();
        }

        /** @var SdkService $sdkService */
        $sdkService = $this->module->get('multisafepay.sdk_service');
        $transactionManager = $sdkService->getSdk()->getTransactionManager();
        $orderReference  = Tools::getValue('transactionid');
        $orderCollection = PrestaShopOrder::getByReference($orderReference);

        foreach ($orderCollection->getResults() as $order) {
            if (!$order->id) {
                LoggerHelper::logWarning('Warning: It seems a notification is trying to process an order which does not exist.');
                header('Content-Type: text/plain');
                die('OK');
            }

            if ($order->module && $order->module !== 'multisafepay') {
                LoggerHelper::logWarning('Warning: It seems a notification is trying to process an order processed by another payment method.');
                header('Content-Type: text/plain');
                die('OK');
            }

            try {
                $transaction = $transactionManager->get($orderReference);
            } catch (ApiException $apiException) {
                LoggerHelper::logError($apiException->getMessage());
                header('Content-Type: text/plain');
                die('OK');
            }

            $this->setNewOrderStatus($order->id, (int)$this->getOrderStatusId($transaction->getStatus()));

            if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
                LoggerHelper::logInfo('A notification has been processed for order ID: ' . $order->id . ' with status: ' . $transaction->getStatus() . ' and PSP ID: ' . $transaction->getTransactionId());
            }
        }

        header('Content-Type: text/plain');
        die('OK');
    }

    /**
     * Change the order status
     *
     * @param int $orderStatusId
     * @return void
     */
    private function setNewOrderStatus(int $orderId, int $orderStatusId): void
    {
        $history           = new PrestaShopOrderHistory();
        $history->id_order = (int)$orderId;
        $history->changeIdOrderState($orderStatusId, $orderId);
        $history->addWithemail();
    }

    /**
     * Return the order status id for the given transaction status
     *
     * @param string $transactionStatus
     * @return string
     */
    private function getOrderStatusId(string $transactionStatus): string
    {
        $orderStatus = array(
            'initialized'      => Configuration::get('MULTISAFEPAY_OS_INITIALIZED'),
            'declined'         => Configuration::get('PS_OS_CANCELED'),
            'cancelled'        => Configuration::get('PS_OS_CANCELED'),
            'completed'        => Configuration::get('PS_OS_PAYMENT'),
            'expired'          => Configuration::get('PS_OS_CANCELED'),
            'uncleared'        => Configuration::get('MULTISAFEPAY_OS_UNCLEARED'),
            'refunded'         => Configuration::get('PS_OS_REFUND'),
            'partial_refunded' => Configuration::get('MULTISAFEPAY_OS_PARTIAL_REFUNDED'),
            'void'             => Configuration::get('PS_OS_CANCELED'),
            'chargedback'      => Configuration::get('MULTISAFEPAY_OS_CHARGEBACK'),
            'shipped'          => Configuration::get('PS_OS_SHIPPING')
        );
        return isset($orderStatus[$transactionStatus]) ? $orderStatus[$transactionStatus] : Configuration::get('PS_OS_ERROR');
    }
}
