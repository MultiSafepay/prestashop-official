<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Wijncadeau extends BasePaymentOption
{
    protected $name = 'Wijncadeau';
    protected $gatewayCode = 'WIJNCADEAU';
    protected $logo = 'wijncadeau.png';
    protected $canProcessRefunds = false;
}
