<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class TrustPay extends BasePaymentOption
{
    protected $name = 'TrustPay';
    protected $gatewayCode = 'TRUSTPAY';
    protected $logo = 'trustpay.png';
}
