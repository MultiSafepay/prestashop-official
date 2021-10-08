<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Amex;
use MultiSafepay\Tests\BaseMultiSafepayTest;

class AmexTest extends BaseMultiSafepayTest
{
    protected $amexPaymentOption;

    public function setUp(): void
    {
        parent::setUp();

        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();

        $mockAmex = $this->getMockBuilder(Amex::class)->setConstructorArgs([$mockMultisafepay])->onlyMethods(['isDirect', 'allowTokenization'])->getMock();
        $mockAmex->method('isDirect')->willReturn(
            false
        );
        $mockAmex->method('allowTokenization')->willReturn(
            false
        );

        $this->amexPaymentOption = $mockAmex;
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Amex::getName
     */
    public function testGetName()
    {
        $output = $this->amexPaymentOption->getName();
        self::assertEquals('American Express', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Amex::getDescription
     */
    public function testGetDescription()
    {
        $output = $this->amexPaymentOption->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Amex::getGatewayCode
     */
    public function testGetGatewayCode()
    {
        $output = $this->amexPaymentOption->getGatewayCode();
        self::assertEquals('AMEX', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Amex::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->amexPaymentOption->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Amex::getLogo
     */
    public function testGetLogo()
    {
        $output = $this->amexPaymentOption->getLogo();
        self::assertEquals('amex.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Amex::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->amexPaymentOption->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'AMEX',
        ], $output);
    }
}
