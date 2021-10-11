<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class IngHomePay extends BasePaymentOption
{
    protected $name = "ING Home'Pay";
    protected $gatewayCode = 'INGHOME';
    protected $logo = 'ing.png';
}
