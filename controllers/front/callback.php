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

use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Services\NotExistingOrderNotificationService;
use MultiSafepay\PrestaShop\Services\OrderService;
use MultiSafepay\PrestaShop\Services\SdkService;

if (!defined('_PS_VERSION_')) {
    exit;
}

class MultisafepayOfficialCallbackModuleFrontController extends ModuleFrontController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Process notification
     *
     * @return void
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function postProcess(): void
    {
        $transactionId = Tools::getValue('transactionid');
        $cart = new Cart($transactionId);

        if ($cart->orderExists()) {
            $order = Order::getByCartId($cart->id);

            $redirectUrl = Context::getContext()->link->getPageLink(
                'order-confirmation',
                null,
                Context::getContext()->language->id,
                'id_cart='.$cart->id.'&id_order='.$order->id.'&id_module='.$this->module->id.'&key='.Context::getContext(
                )->customer->secure_key
            );

            Tools::redirect($redirectUrl);
        }

        $this->context->smarty->assign(
            'spinnerUrl',
            Media::getMediaPath(_PS_MODULE_DIR_ . $this->module->name . '/views/loader/spinner.gif')
        );

        Media::addJsDef(
            [
                'orderExistsEndpoint' => $this->context->link->getModuleLink(
                    'multisafepayofficial',
                    'processorder',
                    [
                        'ajax' => 1,
                        'transactionid' => $transactionId,
                    ],
                    true
                ),
                'orderHistoryUrl' => $this->context->link->getPageLink(
                    'order-confirmation',
                    null,
                    Context::getContext()->language->id,
                    'slowvalidation=1&id_cart='.$cart->id.'&id_order='.null.'&id_module='.$this->module->id.'&key='.Context::getContext(
                    )->customer->secure_key
                )
            ]
        );

        Context::getContext()->controller->registerJavascript(
            'module-multisafepay-check-order-exists-javascript',
            'modules/multisafepayofficial/views/js/multisafepay-process-order.js'
        );

        $this->setTemplate('module:multisafepayofficial/views/templates/front/processing-payment.tpl');
    }
}
