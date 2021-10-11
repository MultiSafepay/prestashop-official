<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Giropay extends BasePaymentOption
{
    protected $name = 'Giropay';
    protected $gatewayCode = 'GIROPAY';
    protected $logo = 'giropay.png';
}
