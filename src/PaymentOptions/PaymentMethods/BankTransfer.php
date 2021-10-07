<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class BankTransfer extends BasePaymentOption
{
    public $hasConfigurableDirect = true;
    protected $name = 'Bank Transfer';
    protected $gatewayCode = 'BANKTRANS';
    protected $logo = 'banktrans.png';
}
