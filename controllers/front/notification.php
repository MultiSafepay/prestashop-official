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
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

use MultiSafepay\PrestaShop\Services\NotExistingOrderNotificationService;
use MultiSafepay\PrestaShop\Services\ExistingOrderNotificationService;

class MultisafepayOfficialNotificationModuleFrontController extends ModuleFrontController
{

    public function __construct()
    {
        parent::__construct();
        $this->ajax = true;
    }

    /**
     * Process notification
     *
     * @return void
     */
    public function postProcess(): void
    {
        /** @var NotExistingOrderNotificationService $notificationService */
        $notificationService = $this->module->get('multisafepay.not_existing_order_notification_service');

        $transaction = $notificationService->getTransactionFromBody(Tools::file_get_contents('php://input'));

        // Orders before version 5.2.0 will not have the cartId saved in var2, therefore if this is empty we get the
        // cart using the transactionid, which in those versions always equals the order reference.
        $cartId = $transaction->getVar2();
        if (empty($cartId)) {
            $orderReference = Tools::getValue('transactionid');
            $orderCollection = Order::getByReference($orderReference);
            /** @var Order $order */
            $order = $orderCollection->getFirst();
            $cart = new Cart($order->id_cart);
        } else {
            $cart = new Cart($cartId);
        }

        // If the order already exists we use a different service to handle the notification
        if ($cart->orderExists()) {
            /** @var ExistingOrderNotificationService $notificationService */
            $notificationService = $this->module->get('multisafepay.existing_order_notification_service');
        }

        try {
            $notificationService->processNotification($transaction, $cart);
        } catch (PrestaShopException $prestaShopException) {
            header('Content-Type: text/plain');
            echo $prestaShopException->getMessage();
        }

        echo ' OK';
    }
}
