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

use Multisafepay;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Services\IssuerService;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultiSafepay\Tests\BaseMultiSafepayTest;

class PaymentOptionServiceTest extends BaseMultiSafepayTest
{
    protected $paymentOptionsService;

    public function setUp(): void
    {
        parent::setUp();

        $mockIssuerService = $this->getMockBuilder(IssuerService::class)->disableOriginalConstructor()->getMock();
        $mockIssuerService->expects(self::once())->method('getIssuers')->willReturn(
            [
                'value' => 1234,
                'name'  => 'Test Issuer',
            ]
        );

        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();
        $mockMultisafepay->expects(self::once())->method('get')->willReturn(
            $mockIssuerService
        );

        $this->paymentOptionsService = new PaymentOptionService($mockMultisafepay);
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
            self::assertInstanceOf(BasePaymentOption::class, $paymentOption);
        }
    }
}
