<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\QrCode;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use Order;

class IdealQr extends BasePaymentOption
{
    protected $name = 'iDEAL QR';
    protected $gatewayCode = 'IDEALQR';
    protected $logo = 'ideal-qr.png';

    public function getGatewayInfo(Order $order, array $data = []): GatewayInfoInterface
    {
        return new QrCode();
    }
}
