<?php declare(strict_types=1);
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

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
            public static function get($key, $idLang = null, $idShopGroup = null, $idShop = null, $default = false): string
            {
                if ($key === 'MULTISAFEPAY_OFFICIAL_API_KEY') {
                    return 'MOCKED-REAL-API-KEY';
                }

                if ($key === 'MULTISAFEPAY_OFFICIAL_TEST_API_KEY') {
                    return 'MOCKED-TEST-API-KEY';
                }
                return '';
            }
        };
        $this->sdkService = $this->getMockBuilder(SdkService::class)->setConstructorArgs([$mockConfiguration])->onlyMethods(['getTestMode'])->getMock();
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\SdkService::getSdk
     */
    public function testGetSdk(): void
    {
        $output = $this->sdkService->getSdk();
        self::assertInstanceOf(Sdk::class, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\SdkService::getApiKey
     */
    public function testGetApiKeyWhenTestModeIsEnable(): void
    {
        $this->sdkService->method('getTestMode')->willReturn(true);
        self::assertEquals('MOCKED-TEST-API-KEY', $this->sdkService->getApiKey());
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\SdkService::getApiKey
     */
    public function testGetApiKeyWhenTestModeIsDisable(): void
    {
        $this->sdkService->method('getTestMode')->willReturn(false);
        self::assertEquals('MOCKED-REAL-API-KEY', $this->sdkService->getApiKey());
    }
}
