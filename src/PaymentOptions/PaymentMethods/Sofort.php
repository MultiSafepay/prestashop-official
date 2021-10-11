<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Sofort extends BasePaymentOption
{
    protected $name = 'Sofort';
    protected $gatewayCode = 'DIRECTBANK';
    protected $logo = 'sofort.png';
}
