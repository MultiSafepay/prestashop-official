<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class CreditCard extends BasePaymentOption
{
    protected $hasConfigurableTokenization = true;
    protected $name = 'Credit card';
    protected $gatewayCode = 'CREDITCARD';
    protected $logo = 'creditcard.png';
}
