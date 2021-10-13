<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class SportEnFit extends BasePaymentOption
{
    protected $name = 'Sport & Fit';
    protected $gatewayCode = 'SPORTENFIT';
    protected $logo = 'sportenfit.png';
    protected $canProcessRefunds = false;
}
