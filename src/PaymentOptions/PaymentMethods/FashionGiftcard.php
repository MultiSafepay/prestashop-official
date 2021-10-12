<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class FashionGiftcard extends BasePaymentOption
{
    protected $name = 'Fashion gift card';
    protected $gatewayCode = 'FASHIONGIFTCARD';
    protected $logo = 'fashiongiftcard.png';
    protected $canProcessRefunds = false;
}
