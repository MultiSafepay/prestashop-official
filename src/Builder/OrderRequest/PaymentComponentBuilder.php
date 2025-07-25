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

namespace MultiSafepay\PrestaShop\Builder\OrderRequest;

use Cart;
use Customer;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use Order;
use Tools;

/**
 * Class PaymentComponentBuilder
 * @package MultiSafepay\PrestaShop\Builder\OrderRequest
 */
class PaymentComponentBuilder implements OrderRequestBuilderInterface
{
    /**
     * @param Cart $cart
     * @param Customer $customer
     * @param BasePaymentOption $paymentOption
     * @param OrderRequest $orderRequest
     * @param Order|null $order
     *
     * @throws InvalidArgumentException
     */
    public function build(
        Cart $cart,
        Customer $customer,
        BasePaymentOption $paymentOption,
        OrderRequest $orderRequest,
        ?Order $order = null
    ): void {
        $payload = $this->getPayload();
        if (!$paymentOption->allowPaymentComponent() || empty($payload)) {
            return;
        }
        $orderRequest->addData(['payment_data' => ['payload' => $payload]]);
        $orderRequest->addType(OrderRequest::DIRECT_TYPE);
    }

    /**
     * Get payload from request
     *
     * @return string|null
     */
    protected function getPayload(): ?string
    {
        return Tools::getValue('payload');
    }
}
