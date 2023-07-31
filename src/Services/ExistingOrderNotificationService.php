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

namespace MultiSafepay\PrestaShop\Services;

use Cart;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultisafepayOfficial;
use Order;
use PrestaShopCollection;
use PrestaShopException;

/**
 * Class ExistingOrderNotificationService
 *
 * @package MultiSafepay\PrestaShop\Services
 */
class ExistingOrderNotificationService extends NotificationService
{
    /**
     * NotificationService constructor.
     *
     * @param MultisafepayOfficial $module
     * @param SdkService $sdkService
     * @param PaymentOptionService $paymentOptionService
     * @param OrderService $orderService
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function __construct(MultisafepayOfficial $module, SdkService $sdkService, PaymentOptionService $paymentOptionService, OrderService $orderService)
    {
        parent::__construct($module, $sdkService, $paymentOptionService, $orderService);
    }

    /**
     * @param TransactionResponse $transaction
     * @param Cart $cart
     * @return void
     * @throws PrestaShopException
     */
    public function processNotification(TransactionResponse $transaction, Cart $cart): void
    {
        $orderCollection = new PrestaShopCollection('Order');
        $orderCollection->where('id_cart', '=', $cart->id);

        /** @var Order $order */
        foreach ($orderCollection->getResults() as $order) {
            $this->processNotificationForOrder($order, $transaction);
        }
    }
}
