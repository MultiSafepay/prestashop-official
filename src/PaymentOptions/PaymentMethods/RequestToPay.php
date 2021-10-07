<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class RequestToPay extends BasePaymentOption
{
    protected $name = 'Request to Pay powered by Deutsche Bank';
    protected $gatewayCode = 'DBRTP';
    protected $logo = 'dbrtp.png';
}
