<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Boekenbon extends BasePaymentOption
{
    protected $name = 'Boekenbon';
    protected $gatewayCode = 'BOEKENBON';
    protected $logo = 'boekenbon.png';
    protected $canProcessRefunds = false;
}
