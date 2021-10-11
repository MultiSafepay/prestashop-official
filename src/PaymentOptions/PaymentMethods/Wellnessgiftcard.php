<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Wellnessgiftcard extends BasePaymentOption
{
    protected $name = 'Wellness gift card';
    protected $gatewayCode = 'WELLNESSGIFTCARD';
    protected $logo = 'wellnessgiftcard.png';
    protected $canProcessRefunds = false;
}
