<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Goodcard extends BasePaymentOption
{
    protected $name = 'Goodcard';
    protected $gatewayCode = 'GOODCARD';
    protected $logo = 'goodcard.png';
    protected $canProcessRefunds = false;
}
