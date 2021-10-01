<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class CreditCard extends BasePaymentOption
{
    public $hasConfigurableTokenization = true;
    protected $name = 'Credit card';
    protected $gatewayCode = 'CREDITCARD';
    protected $logo = 'creditcard.png';
}
