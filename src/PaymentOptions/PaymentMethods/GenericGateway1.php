<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use Configuration;

class GenericGateway1 extends BasePaymentOption
{
    protected $name = 'Generic Gateway 1';

    public function getGatewayCode(): string
    {
        return (Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_CODE_'.$this->getUniqueName()) ?: '');
    }

    public function getLogo(): string
    {
        return (Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_'.$this->getUniqueName()) ?: '');
    }

    public function getUniqueName(): string
    {
        return 'GENERIC1';
    }

    public function getGatewaySettings(): array
    {
        $options = parent::getGatewaySettings();

        $options['MULTISAFEPAY_OFFICIAL_GATEWAY_CODE_'.$this->getUniqueName()] = [
            'type' => 'text',
            'name' => $this->module->l('Gateway code'),
            'value' => Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_CODE_'.$this->getUniqueName()),
            'default' => '',
            'order' => 31,
        ];
        $options['MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_'.$this->getUniqueName()] = [
            'type' => 'text',
            'name' => $this->module->l('Gateway icon'),
            'value' => Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_'.$this->getUniqueName()),
            'helperText' => $this->module->l('Upload the file via FTP to your server and enter the full URL of the payment method icon. Recommended size: 420px * 180px. Recommended format: .png'),
            'default' => '',
            'order' => 32,
        ];

        return $this->sortInputFields($options);
    }
}
