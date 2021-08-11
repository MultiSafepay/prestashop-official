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
        $mockIssuerService->expects($this->once())->method('getIssuers')->willReturn(
            [
                'value' => 1234,
                'name'  => 'Test Issuer',
            ]
        );

        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();
        $mockMultisafepay->expects($this->once())->method('get')->willReturn(
            $mockIssuerService
        );

        $this->idealPaymentOption = new Ideal($mockMultisafepay);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->idealPaymentOption->name;
        $this->assertEquals('iDEAL', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->idealPaymentOption->description;
        $this->assertEquals('', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->idealPaymentOption->gatewayCode;
        $this->assertEquals('IDEAL', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->idealPaymentOption->type;
        $this->assertEquals('redirect', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->idealPaymentOption->icon;
        $this->assertEquals('ideal.png', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getInputFields
     */
    public function testGetInputFields()
    {
        $output = $this->idealPaymentOption->inputs;
        $this->assertIsArray($output);
        $this->assertArrayHasKey('hidden', $output);
        $this->assertArrayHasKey('select', $output);
    }
}
