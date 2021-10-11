<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Kbc extends BasePaymentOption
{
    protected $name = 'KBC';
    protected $gatewayCode = 'KBC';
    protected $logo = 'kbc.png';
}
