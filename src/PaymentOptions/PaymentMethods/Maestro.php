<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Maestro extends BasePaymentOption
{
    protected $hasConfigurableTokenization = true;
    protected $name = 'Maestro';
    protected $gatewayCode = 'MAESTRO';
    protected $logo = 'maestro.png';
}
