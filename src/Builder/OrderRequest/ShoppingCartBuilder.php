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
use Configuration;
use Customer;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart;
use MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShoppingCartBuilderInterface;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Util\CurrencyUtil;
use Order;

/**
 * Class ShoppingCartBuilder
 * @package MultiSafepay\PrestaShop\Builder\OrderRequest
 */
class ShoppingCartBuilder implements OrderRequestBuilderInterface
{
    /**
     * @var ShoppingCartBuilderInterface[]
     */
    private $shoppingCartBuilders;

    /**
     * @var CurrencyUtil
     */
    private $currencyUtil;

    /**
     * ShoppingCartBuilder constructor.
     *
     * @param ShoppingCartBuilderInterface[] $shoppingCartBuilders
     * @param CurrencyUtil $currencyUtil
     */
    public function __construct(array $shoppingCartBuilders, CurrencyUtil $currencyUtil)
    {
        $this->shoppingCartBuilders = $shoppingCartBuilders;
        $this->currencyUtil         = $currencyUtil;
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
        if ((bool)Configuration::get('MULTISAFEPAY_OFFICIAL_DISABLE_SHOPPING_CART')) {
            return;
        }

        $cartSummary = $cart->getSummaryDetails();

        if (Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE')) {
            LoggerHelper::logInfo(
                'Cart Summary for Shopping Cart ID ' . $cart->id . ', contains: ' . json_encode($cartSummary)
            );
        }

        $cartItems = [];
        foreach ($this->shoppingCartBuilders as $shoppingCartBuilder) {
            $cartItems[] = $shoppingCartBuilder->build(
                $cart,
                $cartSummary,
                $this->currencyUtil->getCurrencyIsoCodeById($cart->id_currency)
            );
        }
        $orderRequest->addShoppingCart(new ShoppingCart(array_merge([], ...$cartItems)));
    }
}
