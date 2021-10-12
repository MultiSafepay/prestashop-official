<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class BeautyAndWellness extends BasePaymentOption
{
    protected $name = 'Beauty and wellness';
    protected $gatewayCode = 'BEAUTYANDWELLNESS';
    protected $logo = 'beautywellness.png';
    protected $canProcessRefunds = false;
}
