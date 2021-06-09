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
use OrderCore as PrestaShopOrder;
use OrderHistoryCore as PrestaShopOrderHistory;

class MultisafepayNotificationModuleFrontController extends ModuleFrontController
{

    /**
     * @var int
     */
    private $order_id;

    /**
     * @var PrestaShopOrder
     */
    private $order;

    /**
     * @var TransactionResponse
     */
    private $transaction;

    /**
     *
     * Process notification
     */
    public function postProcess()
    {
        if ($this->module->active == false) {
            die;
        }
        $this->order_id = Tools::getValue('transactionid');
        $this->order    = new PrestaShopOrder(Tools::getValue('transactionid'));
        $this->transaction = (new SdkService())->getSdk()->getTransactionManager()->get($this->order_id);
        $this->setNewOrderStatus($this->getOrderStatusId($this->transaction->getStatus()));
        header('Content-Type: text/plain');
        die('OK');
    }

    private function setNewOrderStatus($order_status_id)
    {
        $history  = new PrestaShopOrderHistory();
        $history->id_order = (int)$this->order->id;
        $history->changeIdOrderState($order_status_id, $this->order->id);
        $history->addWithemail();
    }

    private function getOrderStatusId($transaction_status)
    {
        $order_status = array(
            'initialized' => Configuration::get('MULTISAFEPAY_OS_AWAITING_BANK_TRANSFER_PAYMENT'),
            'declined' => Configuration::get('PS_OS_CANCELED'),
            'cancelled' => Configuration::get('PS_OS_CANCELED'),
            'completed' => Configuration::get('PS_OS_PAYMENT'),
            'expired' => Configuration::get('PS_OS_CANCELED'),
            'uncleared' => Configuration::get('MULTISAFEPAY_OS_UNCLEARED'),
            'refunded' => Configuration::get('PS_OS_REFUND'),
            'partial_refunded' => Configuration::get('MULTISAFEPAY_OS_PARTIAL_REFUNDED'),
            'void' => Configuration::get('PS_OS_CANCELED'),
            'chargedback' => Configuration::get('MULTISAFEPAY_OS_CHARGEBACK'),
            'shipped' => Configuration::get('PS_OS_SHIPPING')
        );
        return isset($order_status[$transaction_status]) ? $order_status[$transaction_status] : Configuration::get('PS_OS_ERROR');
    }
}
