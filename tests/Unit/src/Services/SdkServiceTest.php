<?php declare(strict_types=1);

namespace MultiSafepay\Tests\Services;

use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\Services\SdkService;
use MultiSafepay\Sdk;

class SdkServiceTest extends BaseMultiSafepayTest
{
    protected $sdkService;

    public function setUp(): void
    {
        parent::setUp();

        // Please set an API Key in your env file
        $apiKey = getenv('MULTISAFEPAY_API_KEY') ?: 'FAKE_API_KEY';

        $mockSdkService = $this->createPartialMock(SdkService::class, ['getApiKey', 'getTestMode']);
        $mockSdkService->expects(self::atLeastOnce())->method('getApiKey')->willReturn($apiKey);
        $mockSdkService->expects(self::atLeastOnce())->method('getTestMode')->willReturn(true);

        $this->sdkService = $mockSdkService;
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\SdkService::getSdk
     */
    public function testGetSdk()
    {
        $output = $this->sdkService->getSdk();
        self::assertInstanceOf(Sdk::class, $output);
    }
}
