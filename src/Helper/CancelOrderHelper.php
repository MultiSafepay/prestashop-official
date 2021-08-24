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

namespace MultiSafepay\PrestaShop\Helper;

use Configuration;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use OrderHistory;
use PrestaShopCollection;

class CancelOrderHelper
{
    /**
     * Cancel the orders for the given Order Collection
     *
     * @param PrestaShopCollection $orderCollection
     * @return void
     */
    public static function cancelOrder(PrestaShopCollection $orderCollection): void
    {
        foreach ($orderCollection->getResults() as $order) {
            $history  = new OrderHistory();
            $history->id_order = (int)$order->id;
            $history->id_order_state = (int)$order->id;
            $history->changeIdOrderState((int) Configuration::get('PS_OS_CANCELED'), $order->id);
            $history->addWithemail(true, array('dont_send_email' => true));

            if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
                LoggerHelper::logInfo('Order ID: ' . $order->id . ' has been canceled');
            }
        }
    }
}
