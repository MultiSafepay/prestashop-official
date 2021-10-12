<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class GooglePay extends BasePaymentOption
{
    protected $name = 'Google Pay';
    protected $gatewayCode = 'GOOGLEPAY';
    protected $logo = 'googlepay.png';
}
