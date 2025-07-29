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
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class CustomerBuilder
 * @package MultiSafepay\PrestaShop\Builder\OrderRequest
 */
class CustomerBuilder implements OrderRequestBuilderInterface
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
     * CustomerBuilder constructor.
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
        $invoiceAddress  = $this->addressUtil->getAddress((int)$cart->id_address_invoice);
        $customerAddress = $this->addressBuilder->build($invoiceAddress);

        $orderRequest->addCustomer(
            $this->customerUtil->createCustomer(
                $customerAddress,
                $customer->email,
                $invoiceAddress->phone,
                $invoiceAddress->firstname,
                $invoiceAddress->lastname,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $this->languageUtil->getLanguageCode((int)$cart->id_lang),
                $invoiceAddress->company,
                $this->shouldSendCustomerReference() ? (string)$customer->id : null,
                $this->getCustomerBrowserInfo()
            )
        );
    }

    /**
     * Return if the customer reference should be included according tokenization related settings
     *
     * @return bool
     */
    private function shouldSendCustomerReference(): bool
    {
        if ((bool)Tools::getValue('tokenize', false)) {
            return true;
        }

        if ((bool)Tools::getValue('saveToken', false) === true) {
            return true;
        }

        if ((bool)Tools::getValue('selectedToken', false) === true) {
            return true;
        }

        return false;
    }

    /**
     * Return browser information
     *
     * @return array|null
     */
    private function getCustomerBrowserInfo(): ?array
    {
        $browser = Tools::getValue('browser', '');

        if (! empty($browser)) {
            return json_decode($browser, true);
        }

        return null;
    }
}
