<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Amex extends BasePaymentOption
{
    protected $hasConfigurableTokenization = true;
    protected $name = 'American Express';
    protected $gatewayCode = 'AMEX';
    protected $logo = 'amex.png';
}
