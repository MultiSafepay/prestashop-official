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
use MultiSafepay\PrestaShop\Builder\OrderRequest\CustomerBuilder\AddressBuilder;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Util\AddressUtil;
use MultiSafepay\PrestaShop\Util\CustomerUtil;
use MultiSafepay\PrestaShop\Util\LanguageUtil;
use Order;

/**
 * Class DeliveryBuilder
 * @package MultiSafepay\PrestaShop\Builder\OrderRequest
 */
class DeliveryBuilder implements OrderRequestBuilderInterface
{
    /**
     * @var AddressBuilder
     */
    private $addressBuilder;

    /**
     * @var AddressUtil
     */
    private $addressUtil;

    /**
     * @var CustomerUtil
     */
    private $customerUtil;

    /**
     * @var LanguageUtil
     */
    private $languageUtil;


    /**
     * DeliveryBuilder constructor.
     *
     * @param AddressBuilder $addressBuilder
     * @param AddressUtil $addressUtil
     * @param CustomerUtil $customerUtil
     * @param LanguageUtil $languageUtil
     */
    public function __construct(
        AddressBuilder $addressBuilder,
        AddressUtil $addressUtil,
        CustomerUtil $customerUtil,
        LanguageUtil $languageUtil
    ) {
        $this->addressBuilder = $addressBuilder;
        $this->addressUtil    = $addressUtil;
        $this->customerUtil   = $customerUtil;
        $this->languageUtil   = $languageUtil;
    }

    /**
     * @param Cart $cart
     * @param Customer $customer
     * @param BasePaymentOption $paymentOption
     * @param OrderRequest $orderRequest
     * @param Order|null $order
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function build(
        Cart $cart,
        Customer $customer,
        BasePaymentOption $paymentOption,
        OrderRequest $orderRequest,
        ?Order $order = null
    ): void {
        if ($cart->getTotalShippingCost() <= 0) {
            return;
        }
        $prestashopAddress = $this->addressUtil->getAddress((int)$cart->id_address_delivery);
        $address           = $this->addressBuilder->build($prestashopAddress);

        $orderRequest->addDelivery(
            $this->customerUtil->createCustomer(
                $address,
                $customer->email,
                $prestashopAddress->phone,
                $prestashopAddress->firstname,
                $prestashopAddress->lastname,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $this->languageUtil->getLanguageCode((int)$cart->id_lang),
                $prestashopAddress->company
            )
        );
    }
}
