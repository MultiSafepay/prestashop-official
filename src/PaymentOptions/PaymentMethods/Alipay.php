<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Alipay extends BasePaymentOption
{
    protected $name = 'Alipay';
    protected $gatewayCode = 'ALIPAY';
    protected $logo = 'alipay.png';
}
