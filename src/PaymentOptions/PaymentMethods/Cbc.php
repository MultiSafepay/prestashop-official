<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Cbc extends BasePaymentOption
{
    protected $name = 'CBC';
    protected $gatewayCode = 'CBC';
    protected $logo = 'cbc.png';
}
