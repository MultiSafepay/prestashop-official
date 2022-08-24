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

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use Cart;
use Customer;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BaseGatewayInfo;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta;
use MultiSafepay\Exception\InvalidArgumentException;
use Tools;
use Order;
use Address;
use Context;

class Einvoicing extends BasePaymentOption
{
    public const CLASS_NAME = 'Einvoicing';
    protected $gatewayCode = 'EINVOICE';
    protected $logo = 'einvoice.png';
    protected $hasConfigurableDirect = true;
    protected $canProcessRefunds = false;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->module->l('E-Invoicing', self::CLASS_NAME);
    }

    public function getTransactionType(): string
    {
        $checkoutVars = Tools::getAllValues();
        return (empty($checkoutVars['bankaccount']) || empty($checkoutVars['birthday']))
            ? self::REDIRECT_TYPE : self::DIRECT_TYPE;
    }


    public function getDirectTransactionInputFields(): array
    {
        return [
            [
                'type'          => 'date',
                'name'          => 'birthday',
                'placeholder'   => $this->module->l('Birthday', self::CLASS_NAME),
                'value'         => Context::getContext()->customer->birthday ?? '',
            ],
            [
                'type'          => 'text',
                'name'          => 'bankaccount',
                'placeholder'   => $this->module->l('Bank Account', self::CLASS_NAME),
                'value'         => ''
            ]
        ];
    }

    public function getGatewayInfo(Cart $cart, array $data = []): ?GatewayInfoInterface
    {
        if (empty($data['bankaccount']) && empty($data['birthday'])) {
            return null;
        }

        $gatewayInfo = new Meta();

        $gatewayInfo->addEmailAddressAsString((new Customer($cart->id_customer))->email);
        $gatewayInfo->addPhoneAsString((new Address($cart->id_address_invoice))->phone);
        if (!empty($data['bankaccount'])) {
            $gatewayInfo->addBankAccountAsString($data['bankaccount']);
        }
        if (!empty($data['birthday'])) {
            $gatewayInfo->addBirthdayAsString($data['birthday']);
        }

        return $gatewayInfo;
    }
}
