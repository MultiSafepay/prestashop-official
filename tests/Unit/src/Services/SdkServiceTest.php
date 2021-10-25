<?php declare(strict_types=1);

namespace MultiSafepay\Tests\Services;

use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\Services\SdkService;
use MultiSafepay\Sdk;
use Configuration;

class SdkServiceTest extends BaseMultiSafepayTest
{
    protected $sdkService;

    public function setUp(): void
    {
        parent::setUp();

        $mockConfiguration = new class extends Configuration {
            public static function get($key, $idLang = null, $idShopGroup = null, $idShop = null, $default = false)
            {
                if ($key === 'MULTISAFEPAY_OFFICIAL_API_KEY') {
                    return 'MOCKED-REAL-API-KEY';
                } elseif ($key === 'MULTISAFEPAY_OFFICIAL_TEST_API_KEY') {
                    return 'MOCKED-TEST-API-KEY';
                }
            }
        };
        $this->sdkService = $this->getMockBuilder(SdkService::class)->setConstructorArgs([$mockConfiguration])->onlyMethods(['getTestMode'])->getMock();
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\SdkService::getSdk
     */
    public function testGetSdk()
    {
        $output = $this->sdkService->getSdk();
        self::assertInstanceOf(Sdk::class, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\SdkService::getApiKey
     */
    public function testGetApiKeyWhenTestModeIsEnable()
    {
        $this->sdkService->method('getTestMode')->willReturn(true);
        self::assertEquals('MOCKED-TEST-API-KEY', $this->sdkService->getApiKey());
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\SdkService::getApiKey
     */
    public function testGetApiKeyWhenTestModeIsDisable()
    {
        $this->sdkService->method('getTestMode')->willReturn(false);
        self::assertEquals('MOCKED-REAL-API-KEY', $this->sdkService->getApiKey());
    }
}
