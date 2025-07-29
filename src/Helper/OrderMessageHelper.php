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

namespace MultiSafepay\PrestaShop\Helper;

use Validate;
use Tools;
use CustomerThread;
use CustomerMessage;
use Customer;
use Order;

/**
 * Class OrderMessageHelper
 * @package MultiSafepay\PrestaShop\Helper
 */
class OrderMessageHelper
{
    /**
     * Add message as order note
     *
     * @param Order $order
     * @param string $message
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public static function addMessage(Order $order, string $message): void
    {
        $message = strip_tags($message);

        if (!Validate::isCleanHtml($message)) {
            return;
        }

        /** @var Customer $customer */
        $customer = $order->getCustomer();

        $customerThread = new CustomerThread();
        $customerThread->id_contact = 0;
        $customerThread->id_customer = (int)$order->id_customer;
        $customerThread->id_shop = (int)$order->id_shop;
        $customerThread->id_order = (int)$order->id;
        $customerThread->id_lang = (int)$order->id_lang;
        $customerThread->email = $customer->email;
        $customerThread->status = 'open';
        $customerThread->token = Tools::passwdGen(12);
        $customerThread->add();

        $customerMessage = new CustomerMessage();
        $customerMessage->id_customer_thread = $customerThread->id;
        $customerMessage->id_employee = 0;
        $customerMessage->message = $message;
        $customerMessage->private = true;
        $customerMessage->add();
    }
}
