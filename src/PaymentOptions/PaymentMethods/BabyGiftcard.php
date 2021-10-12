<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class BabyGiftcard extends BasePaymentOption
{
    protected $name = 'Baby Giftcard';
    protected $gatewayCode = 'BABYCAD';
    protected $logo = 'babycad.png';
    protected $canProcessRefunds = false;
}
