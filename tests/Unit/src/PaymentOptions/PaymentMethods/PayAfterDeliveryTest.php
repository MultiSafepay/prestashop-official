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
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\PayAfterDelivery;

class PayAfterDeliveryTest extends BaseMultiSafepayTest
{
    /** @var PayAfterDelivery */
    protected $payAfterDeliveryPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();
        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();
        $mockPayAfterDelivery = $this->getMockBuilder(PayAfterDelivery::class)->setConstructorArgs([$mockMultisafepay])->onlyMethods(['isDirect'])->getMock();
        $mockPayAfterDelivery->method('isDirect')->willReturn(true);
        $this->payAfterDeliveryPaymentMethod = $mockPayAfterDelivery;
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\PayAfterDelivery::getPaymentOptionName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->payAfterDeliveryPaymentMethod->name;
        self::assertEquals('Pay After Delivery', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\PayAfterDelivery::getPaymentOptionDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->payAfterDeliveryPaymentMethod->description;
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\PayAfterDelivery::getPaymentOptionGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->payAfterDeliveryPaymentMethod->gatewayCode;
        self::assertEquals('PAYAFTER', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\PayAfterDelivery::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->payAfterDeliveryPaymentMethod->type;
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\PayAfterDelivery::getPaymentOptionLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->payAfterDeliveryPaymentMethod->icon;
        self::assertEquals('payafter.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\PayAfterDelivery::getInputFields
     */
    public function testGetDirectTransactionInputFields()
    {
        $output = $this->payAfterDeliveryPaymentMethod->getDirectTransactionInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'date',
            'name'  => 'birthday',
            'placeholder' => '',
            'value' => ''
        ], $output);
        self::assertContains([
            'type' => 'text',
            'name'  => 'bankaccount',
            'placeholder' => '',
            'value' => ''
        ], $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\PayAfterDelivery::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->payAfterDeliveryPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'PAYAFTER',
        ], $output);
    }
}
