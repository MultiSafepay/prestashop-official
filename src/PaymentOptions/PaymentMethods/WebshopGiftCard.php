<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class WebshopGiftCard extends BasePaymentOption
{
    protected $name = 'Webshop gift card';
    protected $gatewayCode = 'WEBSHOPGIFTCARD';
    protected $logo = 'webshopgiftcard.png';
    protected $canProcessRefunds = false;
}
