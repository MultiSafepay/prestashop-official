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
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\Tests\PaymentOptions;

use MultisafepayOfficial;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal;
use MultiSafepay\PrestaShop\Services\IssuerService;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use Configuration;

class IdealTest extends BaseMultiSafepayTest
{
    protected $idealPaymentOption;

    public function setUp(): void
    {
        parent::setUp();

        $mockIssuerService = $this->getMockBuilder(IssuerService::class)->disableOriginalConstructor()->getMock();
        $mockIssuerService->method('getIssuers')->willReturn(
            [
                'value' => 1234,
                'name'  => 'Test Issuer',
            ]
        );

        $mockMultisafepay = $this->getMockBuilder(MultisafepayOfficial::class)->getMock();
        $mockMultisafepay->method('get')->with('multisafepay.issuer_service')->willReturn(
            $mockIssuerService
        );

        $mockIdeal = $this->getMockBuilder(Ideal::class)->setConstructorArgs([$mockMultisafepay])->onlyMethods(['isDirect', 'allowTokenization'])->getMock();
        $mockIdeal->method('isDirect')->willReturn(
            false
        );
        $mockIdeal->method('allowTokenization')->willReturn(
            false
        );

        $this->idealPaymentOption = $mockIdeal;
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getDescription
     */
    public function testGetDescription()
    {
        $output = $this->idealPaymentOption->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getGatewayCode
     */
    public function testGetGatewayCode()
    {
        $output = $this->idealPaymentOption->getGatewayCode();
        self::assertEquals('IDEAL', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->idealPaymentOption->getTransactionType();
        self::assertEquals('direct', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getLogo
     */
    public function testGetLogo()
    {
        $output = $this->idealPaymentOption->getLogo();
        self::assertEquals('ideal.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getDirectTransactionInputFields
     */
    public function testGetInputFields()
    {
        $output = $this->idealPaymentOption->getDirectTransactionInputFields();
        self::assertIsArray($output);
        self::assertCount(0, $output);
    }
}
