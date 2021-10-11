<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta;
use Tools;
use Order;
use Address;
use Context;

class AfterPay extends BasePaymentOption
{
    protected $name = 'AfterPay';
    protected $gatewayCode = 'AFTERPAY';
    protected $logo = 'afterpay.png';
    protected $hasConfigurableDirect = true;
    protected $canProcessRefunds = false;

    public function getTransactionType(): string
    {
        $checkoutVars = Tools::getAllValues();
        return (empty($checkoutVars['gender']) || empty($checkoutVars['birthday'])) ? self::REDIRECT_TYPE : self::DIRECT_TYPE;
    }

    public function getDirectTransactionInputFields(): array
    {
        return [
            [
                'type'          => 'select',
                'name'          => 'gender',
                'placeholder'   => $this->module->l('Salutation'),
                'options'       => [
                    [
                        'value' => 'male',
                        'name'  => 'Mr.',
                    ],
                    [
                        'value' => 'female',
                        'name'  => 'Mrs.',
                    ],
                ],
            ],
            [
                'type'          => 'date',
                'name'          => 'birthday',
                'placeholder'   => $this->module->l('Birthday'),
                'value'         => Context::getContext()->customer->birthday ?? '',
            ]
        ];
    }

    public function getGatewayInfo(Order $order, array $data = []): ?GatewayInfoInterface
    {
        if (empty($data['gender']) && empty($data['birthday'])) {
            return null;
        }

        $gatewayInfo = new Meta();
        $gatewayInfo->addEmailAddressAsString($order->getCustomer()->email);
        $gatewayInfo->addPhoneAsString((new Address($order->id_address_invoice))->phone);
        if (!empty($data['gender'])) {
            $gatewayInfo->addGenderAsString($data['gender']);
        }
        if (!empty($data['birthday'])) {
            $gatewayInfo->addBirthdayAsString($data['birthday']);
        }

        return $gatewayInfo;
    }
}
