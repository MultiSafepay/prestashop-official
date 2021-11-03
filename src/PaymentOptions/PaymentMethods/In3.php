<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BaseGatewayInfo;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta;
use Tools;
use Order;
use Address;
use Context;

class In3 extends BasePaymentOption
{
    protected $name = 'in3';
    protected $gatewayCode = 'IN3';
    protected $logo = 'in3.png';
    protected $hasConfigurableDirect = true;
    protected $canProcessRefunds = false;

    /**
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function getTransactionType(): string
    {
        $checkoutVars = Tools::getAllValues();
        return (empty($checkoutVars['gender']) || empty($checkoutVars['birthday'])) ? self::REDIRECT_TYPE : self::DIRECT_TYPE;
    }

    public function getGatewaySettings(): array
    {
        $options = parent::getGatewaySettings();

        $options['MULTISAFEPAY_OFFICIAL_MIN_AMOUNT_'.$this->getUniqueName()]['default'] = '100';
        $options['MULTISAFEPAY_OFFICIAL_MAX_AMOUNT_'.$this->getUniqueName()]['default'] = '3000';

        return $options;
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
                        'value' => 'mr',
                        'name'  => 'Mr.',
                    ],
                    [
                        'value' => 'mrs',
                        'name'  => 'Mrs.',
                    ],
                    [
                        'value' => 'miss',
                        'name'  => 'Miss',
                    ]
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
