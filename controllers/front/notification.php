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
use Tools;

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

        $timestamp           = Tools::getValue( 'timestamp' );
        $transactionid       = Tools::getValue( 'transactionid' );
        $auth                = $request->get_header( 'auth' );
        $body                = $request->get_body();
        $api_key             = ( new SdkService() )->get_api_key();
        $verify_notification = Notification::verifyNotification( $body, $auth, $api_key );

        if ( ! $verify_notification ) {
            $logger = wc_get_logger();
            $logger->log( 'info', 'Notification for transactionid . ' . $transactionid . ' has been received but is not validated' );
            header( 'Content-type: text/plain' );
            die( 'OK' );
        }

        $multisafepay_transaction = new TransactionResponse( $request->get_json_params(), $body );
        ( new PaymentMethodCallback( (string) $transactionid, $multisafepay_transaction ) )->process_callback();


        $transaction_id = '';
        $order_id = '';

        header('Content-Type: text/plain');
        die('OK');

    }

}
