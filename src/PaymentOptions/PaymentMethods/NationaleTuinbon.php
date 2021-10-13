<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class NationaleTuinbon extends BasePaymentOption
{
    protected $name = 'Nationale tuinbon';
    protected $gatewayCode = 'NATIONALETUINBON';
    protected $logo = 'nationaletuinbon.png';
    protected $canProcessRefunds = false;
}
