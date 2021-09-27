<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Services\IssuerService;
use Tools;
use Order;

class Ideal extends BasePaymentOption
{
    public $hasConfigurableDirect = true;
    public $hasConfigurableTokenization = true;

    public function getPaymentOptionName(): string
    {
        return 'iDEAL';
    }

    public function getTransactionType(): string
    {
        $checkoutVars = Tools::getAllValues();
        return empty($checkoutVars['issuer_id']) ? 'redirect' : 'direct';
    }

    public function getPaymentOptionGatewayCode(): string
    {
        return 'IDEAL';
    }

    public function getPaymentOptionLogo(): string
    {
        return 'ideal.png';
    }

    public function getPaymentOptionForm(): bool
    {
        return true;
    }

    public function getDirectTransactionInputFields(): array
    {
        /** @var IssuerService $issuerService */
        $issuerService        = $this->module->get('multisafepay.issuer_service');
        return [
            [
                'type'        => 'select',
                'name'        => 'issuer_id',
                'placeholder' => $this->module->l('Select bank'),
                'options'     => $issuerService->getIssuers($this->getPaymentOptionGatewayCode()),
            ],
        ];
    }

    public function getGatewayInfo(Order $order, array $data = []): GatewayInfoInterface
    {
        if (!isset($data['issuer_id'])) {
            return parent::getGatewayInfo($order, $data);
        }
        $gatewayInfo = new \MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Ideal();
        $gatewayInfo->addIssuerId($data['issuer_id']);
        return $gatewayInfo;
    }
}
