<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Trustly extends BasePaymentOption
{
    protected $name = 'Trustly';
    protected $gatewayCode = 'TRUSTLY';
    protected $logo = 'trustly.png';
}
