<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class WeChatPay extends BasePaymentOption
{
    protected $name = 'WeChat Pay';
    protected $gatewayCode = 'WECHAT';
    protected $logo = 'wechatpay.png';
}
