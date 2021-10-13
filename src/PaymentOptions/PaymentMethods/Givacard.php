<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Givacard extends BasePaymentOption
{
    protected $name = 'Givacard';
    protected $gatewayCode = 'GIVACARD';
    protected $logo = 'givacard.png';
    protected $canProcessRefunds = false;
}
