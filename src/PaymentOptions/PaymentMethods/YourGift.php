<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class YourGift extends BasePaymentOption
{
    protected $name = 'YourGift';
    protected $gatewayCode = 'YOURGIFT';
    protected $logo = 'yourgift.png';
    protected $canProcessRefunds = false;
}
