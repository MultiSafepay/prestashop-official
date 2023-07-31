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

if (!defined('_PS_VERSION_')) {
    exit;
}

class MultisafepayOfficialProcessorderModuleFrontController extends ModuleFrontController
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
     * @throws JsonException
     */
    public function postProcess(): void
    {
        $transactionId = Tools::getValue('transactionid');
        $cart = new Cart($transactionId);

        if (! $cart->orderExists()) {
            http_response_code(400);
            exit;
        }

        $orderId = Order::getIdByCartId($cart->id);

        $redirectUrl = Context::getContext()->link->getPageLink(
            'order-confirmation',
            null,
            Context::getContext()->language->id,
            'id_cart='.$cart->id.'&id_order='.$orderId.'&id_module='.$this->module->id.'&key='.Context::getContext(
            )->customer->secure_key
        );

        exit(json_encode(['redirectUrl' => $redirectUrl]));
    }
}
