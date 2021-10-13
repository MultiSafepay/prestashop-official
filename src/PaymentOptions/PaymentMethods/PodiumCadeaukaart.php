<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class PodiumCadeaukaart extends BasePaymentOption
{
    protected $name = 'Podium cadeaukaart';
    protected $gatewayCode = 'PODIUM';
    protected $logo = 'podium.png';
    protected $canProcessRefunds = false;
}
