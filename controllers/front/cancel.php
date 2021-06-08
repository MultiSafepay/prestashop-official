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

use Order as PrestaShopOrder;
use OrderHistory as PrestaShopOrderHistory;
use Cart as PrestaShopCart;

class MultisafepayCancelModuleFrontController extends ModuleFrontController
{

    /**
     * @todo Refactor this method. This is just a draft to test the order flow
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if ($this->module->active == false) {
            die;
        }

        $order = new PrestaShopOrder(Tools::getValue('id_order'));
        $history  = new PrestaShopOrderHistory();
        $history->id_order = (int)$order->id;
        $history->id_order_state = (int)$order->id;
        $cancel_order_status_id = 6;
        $history->changeIdOrderState($cancel_order_status_id, $order->id);
        $history->add();

        $cart = new PrestaShopCart(Tools::getValue('id_cart'));
        $new_cart = $cart->duplicate();
        Context::getContext()->cookie->id_cart = $new_cart['cart']->id;
        Context::getContext()->cookie->write();

        $redirect = $this->context->link->getPageLink('order', true, null, array('step' => '3'));
        Tools::redirect($redirect);


    }

}
