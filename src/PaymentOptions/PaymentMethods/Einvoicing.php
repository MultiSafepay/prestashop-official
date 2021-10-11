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

class Einvoicing extends BasePaymentOption
{
    protected $name = 'E-Invoicing';
    protected $gatewayCode = 'EINVOICE';
    protected $logo = 'einvoice.png';
    protected $hasConfigurableDirect = true;
    protected $canProcessRefunds = false;

    public function getTransactionType(): string
    {
        $checkoutVars = Tools::getAllValues();
        return (empty($checkoutVars['bankaccount']) || empty($checkoutVars['birthday'])) ? 'redirect' : 'direct';
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

    public function getGatewayInfo(Order $order, array $data = []): ?GatewayInfoInterface
    {
        if (empty($data['bankaccount']) && empty($data['birthday'])) {
            return null;
        }

        $gatewayInfo = new Meta();

        $gatewayInfo->addEmailAddressAsString($order->getCustomer()->email);
        $gatewayInfo->addPhoneAsString((new Address($order->id_address_invoice))->phone);
        if (!empty($data['bankaccount'])) {
            $gatewayInfo->addBankAccountAsString($data['bankaccount']);
        }
        if (!empty($data['birthday'])) {
            $gatewayInfo->addBirthdayAsString($data['birthday']);
        }

        return $gatewayInfo;
    }
}
