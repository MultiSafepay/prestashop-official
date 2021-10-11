<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Santander;

class SantanderTest extends BaseMultiSafepayTest
{
    /** @var Santander */
    protected $santanderPayPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();
        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();
        $mockMultisafepay->method('l')->willReturn(
            ''
        );
        $this->santanderPayPaymentMethod = new Santander($mockMultisafepay);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Santander::getPaymentOptionName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->santanderPayPaymentMethod->getName();
        self::assertEquals('Santander Consumer Finance | Pay per month', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Santander::getPaymentOptionDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->santanderPayPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Santander::getPaymentOptionGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->santanderPayPaymentMethod->getGatewayCode();
        self::assertEquals('SANTANDER', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Santander::getTransactionType
     */
    public function testGetTransactionTypeAsDirect()
    {
        $output = $this->santanderPayPaymentMethod->getTransactionType();
        self::assertEquals('direct', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Santander::getPaymentOptionLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->santanderPayPaymentMethod->getLogo();
        self::assertEquals('betaalplan.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Santander::getInputFields
     */
    public function testGetDirectTransactionInputFields()
    {
        $output = $this->santanderPayPaymentMethod->getDirectTransactionInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'select',
            'name'  => 'gender',
            'placeholder' => '',
            'options' => [
                [
                    'value' => 'male',
                    'name'  => 'Mr.',
                ],
                [
                    'value' => 'female',
                    'name'  => 'Mrs.',
                ],
            ],
        ], $output);
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
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Santander::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->santanderPayPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'SANTANDER',
        ], $output);
    }
}
