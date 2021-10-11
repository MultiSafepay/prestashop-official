<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Paysafecard extends BasePaymentOption
{
    protected $name = 'Paysafecard';
    protected $gatewayCode = 'PSAFECARD';
    protected $logo = 'paysafecard.png';
}
