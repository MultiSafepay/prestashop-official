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

use MultiSafepay\PrestaShop\PaymentOptions\Base\BaseBrandedPaymentOption;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Services\IssuerService;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultiSafepay\PrestaShop\Services\SdkService;
use MultiSafepay\PrestaShop\Services\TokenizationService;
use MultiSafepay\Sdk;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultisafepayOfficial;

class PaymentOptionServiceTest extends BaseMultiSafepayTest
{
    protected $paymentOptionsService;

    protected $mockIssuerService;

    protected $mockTokenizationService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mockIssuerService = $this->getMockBuilder(IssuerService::class)->disableOriginalConstructor()->getMock();
        $this->mockIssuerService->method('getIssuers')->willReturn(
            [
                'value' => 1234,
                'name' => 'Test Issuer',
            ]
        );

        $this->mockTokenizationService = $this->getMockBuilder(TokenizationService::class)->disableOriginalConstructor()->getMock();
        $this->mockTokenizationService->method('createTokenizationCheckoutFields')->willReturn(
            []
        );

        $mockMultisafepay = $this->getMockBuilder(MultisafepayOfficial::class)->getMock();
        $mockMultisafepay->method('get')->willReturnCallback([$this, 'multisafepayGetCallback']);
        $mockMultisafepay->method('l')->willReturn('');

        // Create mock PaymentOptionService
        $this->paymentOptionsService = $this->getMockBuilder(PaymentOptionService::class)
            ->setConstructorArgs([$mockMultisafepay])
            ->onlyMethods(['getMultiSafepayPaymentOptions'])
            ->getMock();

        // Create mock payment options
        $mockBasePaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockBrandedPaymentOption = $this->getMockBuilder(BaseBrandedPaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set up getMultiSafepayPaymentOptions to return our mock payment options
        $this->paymentOptionsService->method('getMultiSafepayPaymentOptions')
            ->willReturn([$mockBasePaymentOption, $mockBrandedPaymentOption]);
    }

    public function multisafepayGetCallback()
    {
        $args = func_get_args();

        if ('multisafepay.issuer_service' === $args[0]) {
            return $this->mockIssuerService;
        }

        if ('multisafepay.tokenization_service' === $args[0]) {
            return $this->mockTokenizationService;
        }

        if ('multisafepay.sdk_service' === $args[0]) {
            $mockSdkService = $this->getMockBuilder(SdkService::class)->disableOriginalConstructor()->getMock();
            $mockSdkService->method('getSdk')->willReturn($this->createMock(Sdk::class));
            return $mockSdkService;
        }

        return null;
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\PaymentOptionService::getMultiSafepayPaymentOptions
     */
    public function testGetMultiSafepayPaymentOptionsReturnArray(): void
    {
        $output = $this->paymentOptionsService->getMultiSafepayPaymentOptions();
        self::assertIsArray($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\PaymentOptionService::getMultiSafepayPaymentOptions
     */
    public function testGetMultiSafepayPaymentOptionsReturnInstanceOfBasePaymentOption(): void
    {
        $paymentOptions = $this->paymentOptionsService->getMultiSafepayPaymentOptions();
        foreach ($paymentOptions as $paymentOption) {
            self::assertTrue(
                ($paymentOption instanceof BasePaymentOption) || ($paymentOption instanceof BaseBrandedPaymentOption),
                'Payment option is not an instance of BasePaymentOption or BaseBrandedPaymentOption'
            );
        }
    }
}
