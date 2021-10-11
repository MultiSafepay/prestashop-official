<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Vvvcadeaukaart extends BasePaymentOption
{
    protected $name = 'VVV Cadeaukaart';
    protected $gatewayCode = 'VVVGIFTCRD';
    protected $logo = 'vvv.png';
    protected $canProcessRefunds = false;
}
