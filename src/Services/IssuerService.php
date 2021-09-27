<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\Services;

/**
 * Class IssuerService
 * @package MultiSafepay\PrestaShop\Services
 */
class IssuerService
{
    /**
     * @var SdkService
     */
    private $sdkService;

    public function __construct(SdkService $sdkService)
    {
        $this->sdkService = $sdkService;
    }

    public function getIssuers(string $gatewayCode): array
    {
        $sdk = $this->sdkService->getSdk();
        if (is_null($sdk)) {
            return [];
        }
        $issuers = $sdk->getIssuerManager()->getIssuersByGatewayCode($gatewayCode);
        $options = [];
        foreach ($issuers as $issuer) {
            $options[] = [
                'value' => $issuer->getCode(),
                'name'  => $issuer->getDescription()
            ];
        }
        return $options;
    }
}
