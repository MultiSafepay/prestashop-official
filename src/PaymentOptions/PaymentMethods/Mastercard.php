<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Mastercard extends BasePaymentOption
{
    protected $hasConfigurableTokenization = true;
    protected $name = 'Mastercard';
    protected $gatewayCode = 'MASTERCARD';
    protected $logo = 'mastercard.png';
}
