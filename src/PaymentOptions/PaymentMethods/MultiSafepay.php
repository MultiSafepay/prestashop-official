<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class MultiSafepay extends BasePaymentOption
{
    protected $name = 'MultiSafepay';
    protected $logo = 'multisafepay.png';

    public function getUniqueName(): string
    {
        return 'MULTISAFEPAY';
    }
}
