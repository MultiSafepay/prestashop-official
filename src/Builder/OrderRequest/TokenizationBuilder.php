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
use Customer;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use Order;
use Tools;

/**
 * Class TokenizationBuilder
 * @package MultiSafepay\PrestaShop\Builder\OrderRequest
 */
class TokenizationBuilder implements OrderRequestBuilderInterface
{
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
        if ($paymentOption->allowTokenization() && !$paymentOption->allowPaymentComponent()) {
            if ($this->shouldSaveToken()) {
                $orderRequest->addRecurringModel('cardOnFile');
            }
            $token = $this->getToken();
            if ($token !== null && 'new' !== $token) {
                $orderRequest->addRecurringModel('cardOnFile');
                $orderRequest->addRecurringId($token);
                $orderRequest->addType(BasePaymentOption::DIRECT_TYPE);
            }
        }
    }

    /**
     * @return bool
     */
    private function shouldSaveToken(): bool
    {
        return (bool)Tools::getValue('saveToken', false) === true;
    }

    /**
     * @return string|null
     */
    private function getToken(): ?string
    {
        return Tools::getValue('selectedToken', null);
    }
}
