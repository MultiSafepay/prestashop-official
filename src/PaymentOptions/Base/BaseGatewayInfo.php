<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\Base;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;

/**
 * Class BaseGatewayInfo
 * @package MultiSafepay\PrestaShop\PaymentOptions\Base
 */
class BaseGatewayInfo implements GatewayInfoInterface
{

    /**
     * @return array
     */
    public function getData(): array
    {
        return [];
    }
}
