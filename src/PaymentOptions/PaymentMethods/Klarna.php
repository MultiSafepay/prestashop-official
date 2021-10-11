<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Klarna extends BasePaymentOption
{
    protected $name = 'Klarna - Pay in 30 days';
    protected $gatewayCode = 'KLARNA';
    protected $logo = 'klarna.png';
    protected $canProcessRefunds = false;
}
