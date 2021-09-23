<?php declare(strict_types=1);

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

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
        $apiKey = getenv('MULTISAFEPAY_API_KEY') ?: '';

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
