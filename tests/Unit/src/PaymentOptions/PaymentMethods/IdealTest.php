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

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
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

        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();
        $mockMultisafepay->method('get')->willReturn(
            $mockIssuerService
        );

        $mockIdeal = $this->getMockBuilder(Ideal::class)->setConstructorArgs([$mockMultisafepay])->onlyMethods(['isDirect'])->getMock();
        $mockIdeal->method('isDirect')->willReturn(
            false
        );

        $this->idealPaymentOption = $mockIdeal;
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->idealPaymentOption->name;
        self::assertEquals('iDEAL', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->idealPaymentOption->description;
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->idealPaymentOption->gatewayCode;
        self::assertEquals('IDEAL', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->idealPaymentOption->type;
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->idealPaymentOption->icon;
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
        self::assertCount(1, $output);
        self::assertArrayHasKey('type', $output[0]);
        self::assertEquals('select', $output[0]['type']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->idealPaymentOption->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'IDEAL',
        ], $output);
    }
}
