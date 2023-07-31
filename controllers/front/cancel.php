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
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Helper\CancelOrderHelper;
use MultiSafepay\PrestaShop\Helper\DuplicateCartHelper;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Services\SdkService;

if (!defined('_PS_VERSION_')) {
    exit;
}

class MultisafepayOfficialCancelModuleFrontController extends ModuleFrontController
{
    /**
     *
     * @return string|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if ($this->module->active == false || !Tools::getValue('id_cart')) {
            if (Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE')) {
                LoggerHelper::logWarning(
                    'It seems postProcess method of cancel controller is being called without the required parameters.'
                );
            }
            header('HTTP/1.0 400 Bad request');
            die();
        }

        /** @var PrestaShopCollection $orderCollection */
        $orderCollection = Order::getByReference(Tools::getValue('id_reference'));

        $cartId = Tools::getValue('id_cart');
        $cart = new Cart($cartId);

        // If order was created before payment, then we need to cancel the order and duplicate the cart
        if ($cart->orderExists()) {
            $orderCollection = new PrestaShopCollection('Order');
            $orderCollection->where('id_cart', '=', $cartId);

            foreach ($orderCollection->getResults() as $order) {
                // Prevent to cancel an order with different secure key
                if (!$this->checkOrderSecureKey($order)) {
                    Tools::redirect($this->context->link->getPageLink('order', true, null, ['step' => '3']));
                }

                // Prevent to cancel an order if the current order status is not initialized or backorder unpaid
                if (!$this->canOrderBeCancelled($order)) {
                    Tools::redirect($this->context->link->getPageLink('order', true, null, ['step' => '3']));
                }
            }

            // Cancel orders
            CancelOrderHelper::cancelOrder($orderCollection);

            // Duplicate cart
            DuplicateCartHelper::duplicateCart($cart);
        }

        try {
            /** @var SdkService $sdkService */
            $sdkService         = $this->get('multisafepay.sdk_service');
            $transactionManager = $sdkService->getSdk()->getTransactionManager();

            $transaction = $transactionManager->get(Tools::getValue('id_reference'));

            if ($transaction->getStatus() !== Transaction::DECLINED) {
                // Redirect to checkout page
                Tools::redirect($this->context->link->getPageLink('order', true, null, ['step' => '3']));
            }
        } catch (ApiException $apiException) {
            LoggerHelper::logError($apiException->getMessage());

            $this->context->smarty->assign(
                [
                    'layout'         => 'full-width-template',
                    'error_message'  => $this->module->l('There was a problem getting the status of
                                                        the transaction, please try again', 'cancel')
                ]
            );

            return $this->setTemplate('module:multisafepayofficial/views/templates/front/error.tpl');
        }

        $this->context->smarty->assign(
            [
                'layout'         => 'full-width-template',
                'error_message'  => $this->module->l('Your transaction was declined, please try again', 'cancel')
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
     * Check if the current order status is initialized or backorder unpaid
     *
     * @param Order $order
     * @return bool
     */
    private function canOrderBeCancelled(Order $order): bool
    {
        if ((int)$order->current_state === (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_INITIALIZED') ||
            (int)$order->current_state === (int)Configuration::get('PS_OS_OUTOFSTOCK_UNPAID')) {
            return true;
        }
        return false;
    }
}
