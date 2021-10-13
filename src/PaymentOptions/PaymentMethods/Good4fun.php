<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Good4fun extends BasePaymentOption
{
    protected $name = 'Good4fun Giftcard';
    protected $gatewayCode = 'GOOD4FUN';
    protected $logo = 'good4fun.png';
    protected $canProcessRefunds = false;
}
