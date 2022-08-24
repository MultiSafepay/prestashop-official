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
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\PrestaShop\Builder\OrderRequest;

use Cart;
use Context;
use Customer;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultisafepayOfficial;
use Order;

/**
 * Class PaymentOptionsBuilder
 * @package MultiSafepay\PrestaShop\Builder\OrderRequest
 */
class PaymentOptionsBuilder implements OrderRequestBuilderInterface
{
    /**
     * @var MultisafepayOfficial
     */
    private $module;

    /**
     * PaymentOptionsBuilder constructor.
     *
     * @param MultisafepayOfficial $module
     */
    public function __construct(MultisafepayOfficial $module)
    {
        $this->module = $module;
    }

    /**
     * @param Cart $cart
     * @param Customer $customer
     * @param BasePaymentOption $paymentOption
     * @param OrderRequest $orderRequest
     * @param Order|null $order
     */
    public function build(
        Cart $cart,
        Customer $customer,
        BasePaymentOption $paymentOption,
        OrderRequest $orderRequest,
        ?Order $order = null
    ): void {
        $paymentOptions = new PaymentOptions();

        $redirectUrl = Context::getContext()->link->getModuleLink('multisafepayofficial', 'callback', [], true);
        $cancelUrl = Context::getContext()->link->getPageLink('order', true, null, ['step' => '3']);
        $secureKey = $customer->secure_key;

        if (isset($order)) {
            $redirectUrl = Context::getContext()->link->getPageLink(
                'order-confirmation',
                null,
                Context::getContext()->language->id,
                'id_cart='.$cart->id.'&id_order='.$order->id.'&id_module='.$this->module->id.'&key='.$secureKey
            );

            $cancelUrl = Context::getContext()->link->getModuleLink(
                'multisafepayofficial',
                'cancel',
                ['id_cart' => $cart->id, 'id_reference' => $order->reference, 'key' => $secureKey],
                true
            );
        }

        $paymentOptions
            ->addNotificationUrl(
                Context::getContext()->link->getModuleLink('multisafepayofficial', 'notification', [], true)
            )
            ->addCancelUrl(
                $cancelUrl
            )
            ->addRedirectUrl(
                $redirectUrl
            );

        $orderRequest->addPaymentOptions($paymentOptions);
    }
}
