<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\AfterPay;

class AfterPayTest extends BaseMultiSafepayTest
{
    /** @var AfterPay */
    protected $afterPayPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();
        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();
        $mockMultisafepay->method('l')->willReturn(
            ''
        );
        $mockAfterPay = $this->getMockBuilder(AfterPay::class)->setConstructorArgs([$mockMultisafepay])->onlyMethods(['isDirect'])->getMock();
        $mockAfterPay->method('isDirect')->willReturn(true);
        $this->afterPayPaymentMethod = $mockAfterPay;
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\AfterPay::getPaymentOptionName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->afterPayPaymentMethod->getName();
        self::assertEquals('AfterPay', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\AfterPay::getPaymentOptionDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->afterPayPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\AfterPay::getPaymentOptionGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->afterPayPaymentMethod->getGatewayCode();
        self::assertEquals('AFTERPAY', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\AfterPay::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->afterPayPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\AfterPay::getPaymentOptionLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->afterPayPaymentMethod->getLogo();
        self::assertEquals('afterpay.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\AfterPay::getInputFields
     */
    public function testGetDirectTransactionInputFields()
    {
        $output = $this->afterPayPaymentMethod->getDirectTransactionInputFields();
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
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\AfterPay::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->afterPayPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'AFTERPAY',
        ], $output);
    }
}
