<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Gezondheidsbon extends BasePaymentOption
{
    protected $name = 'Gezondheidsbon';
    protected $gatewayCode = 'GEZONDHEIDSBON';
    protected $logo = 'gezondheidsbon.png';
    protected $canProcessRefunds = false;
}
