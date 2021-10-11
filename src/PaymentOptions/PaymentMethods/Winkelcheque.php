<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Winkelcheque extends BasePaymentOption
{
    protected $name = 'Winkelcheque';
    protected $gatewayCode = 'WINKELCHEQUE';
    protected $logo = 'winkelcheque.png';
    protected $canProcessRefunds = false;
}
