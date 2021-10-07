<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Bancontact extends BasePaymentOption
{
    protected $name = 'Bancontact';
    protected $gatewayCode = 'MISTERCASH';
    protected $logo = 'bancontact.png';
}
