<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Eps extends BasePaymentOption
{
    protected $name = 'EPS';
    protected $gatewayCode = 'EPS';
    protected $logo = 'eps.png';
}
