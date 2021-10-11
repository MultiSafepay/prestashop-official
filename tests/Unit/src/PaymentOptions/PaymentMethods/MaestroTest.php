<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Maestro;
use MultiSafepay\Tests\BaseMultiSafepayTest;

class MaestroTest extends BaseMultiSafepayTest
{
    protected $maestroPaymentOption;

    public function setUp(): void
    {
        parent::setUp();

        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();

        $mockMaestro = $this->getMockBuilder(Maestro::class)->setConstructorArgs([$mockMultisafepay])->onlyMethods(['isDirect', 'allowTokenization'])->getMock();
        $mockMaestro->method('isDirect')->willReturn(
            false
        );
        $mockMaestro->method('allowTokenization')->willReturn(
            false
        );

        $this->maestroPaymentOption = $mockMaestro;
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Maestro::getName
     */
    public function testGetName()
    {
        $output = $this->maestroPaymentOption->getName();
        self::assertEquals('Maestro', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Maestro::getDescription
     */
    public function testGetDescription()
    {
        $output = $this->maestroPaymentOption->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Maestro::getGatewayCode
     */
    public function testGetGatewayCode()
    {
        $output = $this->maestroPaymentOption->getGatewayCode();
        self::assertEquals('MAESTRO', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Maestro::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->maestroPaymentOption->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Maestro::getLogo
     */
    public function testGetLogo()
    {
        $output = $this->maestroPaymentOption->getLogo();
        self::assertEquals('maestro.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Maestro::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->maestroPaymentOption->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'MAESTRO',
        ], $output);
    }
}
