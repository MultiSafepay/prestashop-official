<?php declare(strict_types=1);

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BaseGatewayInfo;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Account;
use MultiSafepay\ValueObject\IbanNumber;
use MultiSafepay\Exception\InvalidArgumentException;
use Tools;
use PaymentModule;

class Dirdeb extends BasePaymentOption
{

    public function getPaymentOptionName(): string
    {
        return 'SEPA Direct Debit';
    }

    public function getTransactionType(): string
    {
        $checkoutVars = Tools::getAllValues();
        return (empty($checkoutVars['bankaccount']) || empty($checkoutVars['account_holder_name'])) ? 'redirect' : 'direct';
    }

    public function getPaymentOptionGatewayCode(): string
    {
        return 'DIRDEB';
    }

    public function getPaymentOptionDescription(): string
    {
        return '';
    }

    public function getPaymentOptionLogo(): string
    {
        return 'dirdeb.png';
    }

    public function getPaymentOptionForm(): bool
    {
        return true;
    }

    public function getInputFields(): array
    {
        $parentInputs        = parent::getInputFields();
        $paymentMethodInput = [
            'text' => [
                [
                    'name'          => 'account_holder_name',
                    'placeholder'   => $this->module->l('Account Holder Name'),
                    'value'         => ''
                ],
                [
                    'name'          => 'bankaccount',
                    'placeholder'   => $this->module->l('Bank Account'),
                    'value'         => ''
                ]
            ],
            'hidden' => [
                [
                    'name'          => 'emandate',
                    'placeholder'   => '',
                    'value'         => '1'
                ]
            ]
        ];
        return array_merge_recursive($parentInputs, $paymentMethodInput);
    }

    public function getGatewayInfo(array $data = []): GatewayInfoInterface
    {
        if (empty($data['bankaccount']) && empty($data['account_holder_name'])) {
            return new BaseGatewayInfo();
        }

        try {
            $ibanNumber = new IbanNumber($data['bankaccount']);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return new BaseGatewayInfo();
        }

        $gatewayInfo = new Account();
        $gatewayInfo->addAccountId($ibanNumber);
        $gatewayInfo->addAccountHolderIban($ibanNumber);
        $gatewayInfo->addAccountHolderName($data['account_holder_name']);
        return $gatewayInfo;
    }
}
