<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta;
use MultiSafepay\Exception\InvalidArgumentException;
use Tools;
use Order;
use Address;
use Context;

class PayAfterDelivery extends BasePaymentOption
{
    protected $name = 'Pay After Delivery';
    protected $gatewayCode = 'PAYAFTER';
    protected $logo = 'payafter.png';
    protected $hasConfigurableDirect = true;
    protected $canProcessRefunds = false;

    public function getTransactionType(): string
    {
        $checkoutVars = Tools::getAllValues();
        return (empty($checkoutVars['bankaccount']) || empty($checkoutVars['birthday'])) ? self::REDIRECT_TYPE : self::DIRECT_TYPE;
    }

    public function getDirectTransactionInputFields(): array
    {
        return [
            [
                'type'          => 'date',
                'name'          => 'birthday',
                'placeholder'   => $this->module->l('Birthday'),
                'value'         => Context::getContext()->customer->birthday ?? '',
                'order'         => 1,
            ],
            [
                'type'          => 'text',
                'name'          => 'bankaccount',
                'placeholder'   => $this->module->l('Bank Account'),
                'value'         => '',
                'order'         => 2,
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
        $gatewayInfo->addBankAccountAsString($data['bankaccount']);
        $gatewayInfo->addBirthdayAsString($data['birthday']);
        return $gatewayInfo;
    }
}
