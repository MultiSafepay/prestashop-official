<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Account;
use MultiSafepay\ValueObject\IbanNumber;
use MultiSafepay\Exception\InvalidArgumentException;
use Tools;
use Order;

class Dirdeb extends BasePaymentOption
{
    protected $hasConfigurableDirect = true;
    protected $hasConfigurableTokenization = true;
    protected $name = 'SEPA Direct Debit';
    protected $gatewayCode = 'DIRDEB';
    protected $logo = 'dirdeb.png';

    /**
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function getTransactionType(): string
    {
        $checkoutVars = Tools::getAllValues();
        return (empty($checkoutVars['bankaccount']) || empty($checkoutVars['account_holder_name'])) ? self::REDIRECT_TYPE : self::DIRECT_TYPE;
    }

    public function getDirectTransactionInputFields(): array
    {
        return [
            [
                'type'          => 'text',
                'name'          => 'account_holder_name',
                'placeholder'   => $this->module->l('Account Holder Name'),
                'value'         => '',
            ],
            [
                'type'          => 'text',
                'name'          => 'bankaccount',
                'placeholder'   => $this->module->l('Bank Account'),
                'value'         => '',
            ],
            [
                'type'          => 'hidden',
                'name'          => 'emandate',
                'placeholder'   => '',
                'value'         => '1',
            ]
        ];
    }

    public function getGatewayInfo(Order $order, array $data = []): ?GatewayInfoInterface
    {
        if (empty($data['bankaccount']) && empty($data['account_holder_name'])) {
            return null;
        }

        try {
            $ibanNumber = new IbanNumber($data['bankaccount']);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return null;
        }

        $gatewayInfo = new Account();
        $gatewayInfo->addAccountId($ibanNumber);
        $gatewayInfo->addAccountHolderIban($ibanNumber);
        $gatewayInfo->addAccountHolderName($data['account_holder_name']);
        return $gatewayInfo;
    }
}
