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

namespace MultiSafepay\Tests\PaymentOptions\PaymentMethods;

use MultisafepayOfficial;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\PayAfterDelivery;

class PayAfterDeliveryTest extends BaseMultiSafepayTest
{
    /** @var PayAfterDelivery */
    protected $payAfterDeliveryPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();
        $mockMultisafepay = $this->getMockBuilder(MultisafepayOfficial::class)->getMock();
        $mockPayAfterDelivery = $this->getMockBuilder(PayAfterDelivery::class)->setConstructorArgs([$mockMultisafepay])->onlyMethods(['isDirect'])->getMock();
        $mockPayAfterDelivery->method('isDirect')->willReturn(true);
        $this->payAfterDeliveryPaymentMethod = $mockPayAfterDelivery;
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\PayAfterDelivery::getDescription
     */
    public function testGetDescription(): void
    {
        $output = $this->payAfterDeliveryPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\PayAfterDelivery::getGatewayCode
     */
    public function testGetGatewayCode(): void
    {
        $output = $this->payAfterDeliveryPaymentMethod->getGatewayCode();
        self::assertEquals('PAYAFTER', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\PayAfterDelivery::getTransactionType
     */
    public function testGetTransactionType(): void
    {
        $output = $this->payAfterDeliveryPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\PayAfterDelivery::getLogo
     */
    public function testGetLogo(): void
    {
        $output = $this->payAfterDeliveryPaymentMethod->getLogo();
        self::assertEquals('payafter.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\PayAfterDelivery::getDirectTransactionInputFields
     */
    public function testGetDirectTransactionInputFields(): void
    {
        $output = $this->payAfterDeliveryPaymentMethod->getDirectTransactionInputFields();
        self::assertIsArray($output);

        // Get the actual fields from the output
        $birthdayField = array_filter($output, static function ($field) {
            return $field['name'] === 'birthday';
        });

        $bankAccountField = array_filter($output, static function ($field) {
            return $field['name'] === 'bankaccount';
        });

        // Assert that both fields exist
        self::assertNotEmpty($birthdayField, 'Birthday field is missing');
        self::assertNotEmpty($bankAccountField, 'Bank account field is missing');

        // Assert the structure of the birthday field
        $birthdayField = reset($birthdayField);
        self::assertEquals('date', $birthdayField['type']);
        self::assertEquals('birthday', $birthdayField['name']);
        self::assertArrayHasKey('placeholder', $birthdayField);
        self::assertArrayHasKey('value', $birthdayField);

        // Assert the structure of the bank account field
        $bankAccountField = reset($bankAccountField);
        self::assertEquals('text', $bankAccountField['type']);
        self::assertEquals('bankaccount', $bankAccountField['name']);
        self::assertArrayHasKey('placeholder', $bankAccountField);
        self::assertArrayHasKey('value', $bankAccountField);
    }
}
