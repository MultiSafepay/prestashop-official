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
use MultiSafepay\Util\Notification;
use OrderCore as PrestaShopOrder;
use OrderHistoryCore as PrestaShopOrderHistory;

class MultisafepayNotificationModuleFrontController extends ModuleFrontController
{

    public function initHeader() {

    }
    /**
     * @todo Process Notification
     *
     * Process notification
     */
    public function postProcess()
    {
        if ($this->module->active == false) {
            die;
        }

        $order = new PrestaShopOrder(Tools::getValue('transactionid'));
        $history  = new PrestaShopOrderHistory();
        $history->id_order = (int)$order->id;
        $history->id_order_state = (int)$order->id;
        $status_id = 20;
        // Deliveried status
//        $status_id = 5;
        $history->changeIdOrderState($status_id, $order->id);
        $history->add();

        header('Content-Type: text/plain');
        die('OK');

    }

}
