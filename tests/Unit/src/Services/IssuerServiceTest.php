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

use MultiSafepay\Api\IssuerManager;
use MultiSafepay\Api\Issuers\Issuer;
use MultiSafepay\PrestaShop\Services\IssuerService;
use MultiSafepay\PrestaShop\Services\SdkService;
use MultiSafepay\Sdk;
use MultiSafepay\Tests\BaseMultiSafepayTest;

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
    public function testGetIssuers(): void
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
