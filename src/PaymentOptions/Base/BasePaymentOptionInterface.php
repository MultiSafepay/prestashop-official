<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\Base;

use Order;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;

interface BasePaymentOptionInterface
{
    /**
     *
     * @return string
     */
    public function getPaymentOptionName(): string;

    /**
     *
     * @return string
     */
    public function getPaymentOptionDescription(): string;

    /**
     *
     * @return string
     */
    public function getPaymentOptionGatewayCode(): string;

    /**
     *
     * @return string
     */
    public function getTransactionType(): string;

    /**
     *
     * @return string
     */
    public function getPaymentOptionLogo(): string;

    /**
     * @return string
     */
    public function getUniqueName(): string;

    /**
     * @return array
     */
    public function getGatewaySettings(): array;

    /**
     * @param Order $order
     * @param array $data
     * @return GatewayInfoInterface
     */
    public function getGatewayInfo(Order $order, array $data = []): GatewayInfoInterface;

    /**
     * @return bool
     */
    public function canProcessRefunds(): bool;
}
