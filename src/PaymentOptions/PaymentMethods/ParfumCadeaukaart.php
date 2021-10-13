<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class ParfumCadeaukaart extends BasePaymentOption
{
    protected $name = 'Parfum cadeaukaart';
    protected $gatewayCode = 'PARFUMCADEAUKAART';
    protected $logo = 'parfumcadeaukaart.png';
    protected $canProcessRefunds = false;
}
