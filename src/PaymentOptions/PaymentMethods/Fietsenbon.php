<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Fietsenbon extends BasePaymentOption
{
    protected $name = 'Fietsenbon';
    protected $gatewayCode = 'FIETSENBON';
    protected $logo = 'fietsenbon.png';
    protected $canProcessRefunds = false;
}
