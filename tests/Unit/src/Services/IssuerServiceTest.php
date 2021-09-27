<?php declare(strict_types=1);

namespace MultiSafepay\Tests\Services;

use MultiSafepay\Api\IssuerManager;
use MultiSafepay\Api\Issuers\Issuer;
use MultiSafepay\PrestaShop\Services\SdkService;
use MultiSafepay\Sdk;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\Services\IssuerService;

class IssuerServiceTest extends BaseMultiSafepayTest
{
    protected $issuerService;

    public function setUp(): void
    {
        parent::setUp();

        $mockIssuerManager = $this->getMockBuilder(IssuerManager::class)->disableOriginalConstructor()->getMock();
        $mockIssuerManager->expects(self::once())->method('getIssuersByGatewayCode')->willReturn(
            [new Issuer('IDEAL', '1234', 'Test description')]
        );

        $mockSdk = $this->getMockBuilder(Sdk::class)->disableOriginalConstructor()->getMock();
        $mockSdk->expects(self::once())->method('getIssuerManager')->willReturn($mockIssuerManager);

        $mockSdkService = $this->getMockBuilder(SdkService::class)->getMock();
        $mockSdkService->expects(self::once())->method('getSdk')->willReturn($mockSdk);

        $this->issuerService = new IssuerService($mockSdkService);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\IssuerService::getIssuers
     */
    public function testGetIssuers()
    {
        $output = $this->issuerService->getIssuers('IDEAL');
        self::assertIsArray($output);
        foreach ($output as $value) {
            self::assertIsArray($value);
            self::assertArrayHasKey('value', $value);
            self::assertArrayHasKey('name', $value);
        }
    }
}
