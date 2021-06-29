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

use OrderCore as PrestaShopOrder;
use OrderHistoryCore as PrestaShopOrderHistory;
use CartCore as PrestaShopCart;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;

class MultisafepayCancelModuleFrontController extends ModuleFrontController
{
    /**
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess(): void
    {
        if ($this->module->active == false || !Tools::getValue('id_order')) {
            die;
        }

        $order = new PrestaShopOrder(Tools::getValue('id_order'));
        $this->cancelOrder($order);

        $cart = new PrestaShopCart(Tools::getValue('id_cart'));
        $this->duplicateCart($cart);

        Tools::redirect($this->context->link->getPageLink('order', true, null, array('step' => '3')));
    }


    /**
     * @return void
     * @param CartCore $cart
     */
    private function duplicateCart(PrestaShopCart $cart): void
    {
        $duplicated_cart         = $cart->duplicate();
        $this->context->cart     = $duplicated_cart['cart'];
        $this->context->customer = new Customer((int) $cart->id_customer);
        $this->context->currency = new Currency((int) $cart->id_currency);
        $this->context->language = new Language((int) $cart->id_lang);
        $this->context->cookie->__set('id_cart', $duplicated_cart['cart']->id);

        if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
            LoggerHelper::logInfo('Cart ID: ' . $cart->id . ' has been duplicated');
        }
    }

    /**
     * @param OrderCore $order
     * @return void
     */
    private function cancelOrder(PrestaShopOrder $order): void
    {
        $history  = new PrestaShopOrderHistory();
        $history->id_order = (int)$order->id;
        $history->id_order_state = (int)$order->id;
        $history->changeIdOrderState((int) Configuration::get('PS_OS_CANCELED'), $order->id);
        $history->addWithemail(true, array('dont_send_email' => true));

        if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
            LoggerHelper::logInfo('Order ID: ' . $order->id . ' has been canceled');
        }
    }
}
