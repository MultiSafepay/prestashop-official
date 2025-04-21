<?php declare(strict_types=1);
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\Tests\Builder\OrderRequest;

use Cart;
use Customer;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\PrestaShop\Builder\OrderRequest\OrderRequestBuilderInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use Order;

/**
 * Test version of TokenizationBuilder that doesn't depend on Tools::getValue
 */
class TestTokenizationBuilder implements OrderRequestBuilderInterface
{
    private $shouldSaveToken = false;
    private $token = null;

    public function setShouldSaveToken(bool $shouldSave): void
    {
        $this->shouldSaveToken = $shouldSave;
    }

    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function build(
        Cart $cart,
        Customer $customer,
        BasePaymentOption $paymentOption,
        OrderRequest $orderRequest,
        ?Order $order = null
    ): void {
        if ($paymentOption->allowTokenization() && !$paymentOption->allowPaymentComponent()) {
            if ($this->shouldSaveToken) {
                $orderRequest->addRecurringModel('cardOnFile');
            }
            if ($this->token !== null && 'new' !== $this->token) {
                $orderRequest->addRecurringModel('cardOnFile');
                $orderRequest->addRecurringId((string)$this->token);
                $orderRequest->addType(OrderRequest::DIRECT_TYPE);
            }
        }
    }
}
