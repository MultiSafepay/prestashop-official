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
            $history->addWithemail(true, ['send_email' => true]);

            LoggerHelper::log(
                'info',
                'Order has been canceled',
                true,
                $order ? (string)$order->id : null,
                $order->id_cart ?? null
            );
        }
    }
}
