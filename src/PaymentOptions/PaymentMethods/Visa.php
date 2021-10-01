<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Visa extends BasePaymentOption
{
    protected $name = 'Visa';
    protected $gatewayCode = 'VISA';
    protected $logo = 'visa.png';
    protected $hasConfigurableTokenization = true;
}
