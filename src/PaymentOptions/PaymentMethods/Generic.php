<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use Configuration;

class Generic extends BasePaymentOption
{

    public function getPaymentOptionName(): string
    {
        return 'Generic Gateway';
    }

    public function getPaymentOptionGatewayCode(): string
    {
        return (Configuration::get('MULTISAFEPAY_GATEWAY_CODE_'.$this->getUniqueName()) ?: '');
    }

    public function getTransactionType(): string
    {
        return 'redirect';
    }

    public function getPaymentOptionLogo(): string
    {
        return (Configuration::get('MULTISAFEPAY_GATEWAY_CODE_'.$this->getUniqueName()) ?: '');
    }

    public function getUniqueName(): string
    {
        return 'GENERIC';
    }

    public function getGatewaySettings(): array
    {
        $options = parent::getGatewaySettings();

        $options['MULTISAFEPAY_GATEWAY_CODE_'.$this->getUniqueName()] = [
            'type' => 'text',
            'name' => $this->module->l('Gateway code'),
            'value' => Configuration::get('MULTISAFEPAY_GATEWAY_CODE_'.$this->getUniqueName()),
            'default' => '',
            'order' => 31,
        ];
        $options['MULTISAFEPAY_GATEWAY_IMAGE_'.$this->getUniqueName()] = [
            'type' => 'text',
            'name' => $this->module->l('Gateway icon'),
            'value' => Configuration::get('MULTISAFEPAY_GATEWAY_IMAGE_'.$this->getUniqueName()),
            'default' => '',
            'order' => 32,
        ];

        uasort($options, function ($a, $b) {
            return $a['order'] - $b['order'];
        });
        return $options;
    }
}
