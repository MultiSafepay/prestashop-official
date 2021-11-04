<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use Context;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class ApplePay extends BasePaymentOption
{
    protected $name = 'Apple Pay';
    protected $gatewayCode = 'APPLEPAY';
    protected $logo = 'applepay.png';

    public function registerJavascript(Context $context): void
    {
        $context->controller->registerJavascript(
            'module-multisafepay-applepay-javascript',
            'modules/multisafepayofficial/views/js/multisafepay-applepay.js',
            [
                'priority'   => 200,
                'attributes' => 'defer',
            ]
        );
    }
}
