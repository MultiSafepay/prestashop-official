<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Belfius extends BasePaymentOption
{
    protected $name = 'Belfius';
    protected $gatewayCode = 'BELFIUS';
    protected $logo = 'belfius.png';
}
