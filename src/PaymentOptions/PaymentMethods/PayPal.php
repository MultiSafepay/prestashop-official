<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class PayPal extends BasePaymentOption
{
    protected $name = 'PayPal';
    protected $gatewayCode = 'PAYPAL';
    protected $logo = 'paypal.png';
}
