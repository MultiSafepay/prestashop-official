<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class FashionCheque extends BasePaymentOption
{
    protected $name = 'Fashioncheque';
    protected $gatewayCode = 'FASHIONCHEQUE';
    protected $logo = 'fashioncheque.png';
    protected $canProcessRefunds = false;
}
