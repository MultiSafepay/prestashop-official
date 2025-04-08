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

use Exception;
use MultisafepayOfficial;
use MultiSafepay\PrestaShop\Services\TokenizationService;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use Psr\Http\Client\ClientExceptionInterface;

class TokenizationServiceTest extends BaseMultiSafepayTest
{
    /**
     * @covers \MultiSafepay\PrestaShop\Services\TokenizationService::createTokenizationCheckoutFields
     * @throws Exception
     */
    public function testSaveTokenField(): void
    {
        $mockMultisafepay = $this->getMockBuilder(MultisafepayOfficial::class)->getMock();
        $mockMultisafepay->method('l')->willReturn(
            ''
        );

        $mockTokenizationService = $this->getMockBuilder(TokenizationService::class)->setConstructorArgs(
            [$mockMultisafepay, $this->container->get('multisafepay.sdk_service')]
        )->onlyMethods([])->getMock();

        $output = $mockTokenizationService->createTokenizationSavePaymentDetailsCheckbox();

        self::assertCount(1, $output);
        self::assertCount(3, $output[0]);
        self::assertEquals('checkbox', $output[0]['type']);
        self::assertEquals('saveToken', $output[0]['name']);
    }
}
