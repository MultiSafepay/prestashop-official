<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BaseGatewayInfo;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Account;
use MultiSafepay\ValueObject\IbanNumber;
use MultiSafepay\Exception\InvalidArgumentException;
use Tools;
use Order;
use PaymentModule;

class Dirdeb extends BasePaymentOption
{
    public $hasConfigurableDirect = true;

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

    public function getDirectTransactionInputFields(): array
    {
        return [
            [
                'type'          => 'text',
                'name'          => 'account_holder_name',
                'placeholder'   => $this->module->l('Account Holder Name'),
                'value'         => ''
            ],
            [
                'type'          => 'text',
                'name'          => 'bankaccount',
                'placeholder'   => $this->module->l('Bank Account'),
                'value'         => ''
            ],
            [
                'type'          => 'hidden',
                'name'          => 'emandate',
                'placeholder'   => '',
                'value'         => '1'
            ]
        ];
    }

    public function getGatewayInfo(Order $order, array $data = []): GatewayInfoInterface
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
