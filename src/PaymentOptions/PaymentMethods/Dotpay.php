<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Dotpay extends BasePaymentOption
{
    protected $name = 'Dotpay';
    protected $gatewayCode = 'DOTPAY';
    protected $logo = 'dotpay.png';
}
