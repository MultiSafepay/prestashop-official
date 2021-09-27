<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BaseGatewayInfo;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta;
use MultiSafepay\Exception\InvalidArgumentException;
use Tools;
use Order;
use Address;
use Context;

class PayAfterDelivery extends BasePaymentOption
{
    public $hasConfigurableDirect = true;

    public function getPaymentOptionName(): string
    {
        return 'Pay After Delivery';
    }

    public function getTransactionType(): string
    {
        $checkoutVars = Tools::getAllValues();
        return (empty($checkoutVars['bankaccount']) || empty($checkoutVars['birthday'])) ? 'redirect' : 'direct';
    }

    public function getPaymentOptionGatewayCode(): string
    {
        return 'PAYAFTER';
    }

    public function getPaymentOptionDescription(): string
    {
        return '';
    }

    public function getPaymentOptionLogo(): string
    {
        return 'payafter.png';
    }

    public function getPaymentOptionForm(): bool
    {
        return true;
    }

    public function getDirectTransactionInputFields(): array
    {
        return [
            [
                'type'          => 'date',
                'name'          => 'birthday',
                'placeholder'   => $this->module->l('Birthday'),
                'value'         => Context::getContext()->customer->birthday ?? '',
            ],
            [
                'type'          => 'text',
                'name'          => 'bankaccount',
                'placeholder'   => $this->module->l('Bank Account'),
                'value'         => ''
            ]
        ];
    }

    public function getGatewayInfo(Order $order, array $data = []): GatewayInfoInterface
    {
        if (empty($data['bankaccount']) && empty($data['birthday'])) {
            return new BaseGatewayInfo();
        }

        $gatewayInfo = new Meta();

        $gatewayInfo->addEmailAddressAsString($order->getCustomer()->email);
        $gatewayInfo->addPhoneAsString((new Address($order->id_address_invoice))->phone);
        $gatewayInfo->addBankAccountAsString($data['bankaccount']);
        $gatewayInfo->addBirthdayAsString($data['birthday']);
        return $gatewayInfo;
    }

    public function canProcessRefunds(): bool
    {
        return false;
    }
}
