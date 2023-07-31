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

namespace MultiSafepay\PrestaShop\Builder;

use Cart;
use Customer;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\PrestaShop\Builder\OrderRequest\OrderRequestBuilderInterface;
use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Util\CurrencyUtil;
use Order;

/**
 * Class OrderRequestBuilder
 * @package MultiSafepay\PrestaShop\Builder
 */
class OrderRequestBuilder
{
    /**
     * @var OrderRequestBuilderInterface[]
     */
    private $orderRequestBuilders;

    /**
     * @var CurrencyUtil
     */
    private $currencyUtil;

    /**
     * OrderRequestBuilder constructor.
     *
     * @param OrderRequestBuilderInterface[] $orderRequestBuilders
     * @param CurrencyUtil $currencyUtil
     */
    public function __construct(array $orderRequestBuilders, CurrencyUtil $currencyUtil)
    {
        $this->orderRequestBuilders  = $orderRequestBuilders;
        $this->currencyUtil = $currencyUtil;
    }

    public function build(
        Cart $cart,
        Customer $customer,
        BasePaymentOption $paymentOption,
        ?Order $order = null
    ): OrderRequest {
        $currencyCode = $this->currencyUtil->getCurrencyIsoCodeById($cart->id_currency);
        $orderRequest = new OrderRequest();

        $orderRequest
            ->addOrderId($order->reference ?? (string)$cart->id)
            ->addMoney(
                MoneyHelper::createMoney(
                    (float)$cart->getOrderTotal(),
                    $currencyCode
                )
            )
            ->addGatewayCode($paymentOption->getGatewayCode())
            ->addType($paymentOption->getTransactionType())
            ->addData(['var2' => $cart->id]);

        foreach ($this->orderRequestBuilders as $orderRequestBuilder) {
            $orderRequestBuilder->build($cart, $customer, $paymentOption, $orderRequest, $order);
        }

        return $orderRequest;
    }
}
