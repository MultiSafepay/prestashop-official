<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class MultiSafepay extends BasePaymentOption
{

    public function getPaymentOptionName(): string
    {
        return 'MultiSafepay';
    }

    public function getPaymentOptionGatewayCode(): string
    {
        return '';
    }

    public function getTransactionType(): string
    {
        return 'redirect';
    }

    public function getPaymentOptionLogo(): string
    {
        return 'multisafepay.png';
    }

    public function getUniqueName(): string
    {
        return 'MULTISAFEPAY';
    }
}
