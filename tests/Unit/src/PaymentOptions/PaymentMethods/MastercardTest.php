<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Mastercard;
use MultiSafepay\Tests\BaseMultiSafepayTest;

class MastercardTest extends BaseMultiSafepayTest
{
    protected $mastercardPaymentOption;

    public function setUp(): void
    {
        parent::setUp();

        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();

        $mockMastercard = $this->getMockBuilder(Mastercard::class)->setConstructorArgs([$mockMultisafepay])->onlyMethods(['isDirect', 'allowTokenization'])->getMock();
        $mockMastercard->method('isDirect')->willReturn(
            false
        );
        $mockMastercard->method('allowTokenization')->willReturn(
            false
        );

        $this->mastercardPaymentOption = $mockMastercard;
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Mastercard::getName
     */
    public function testGetName()
    {
        $output = $this->mastercardPaymentOption->getName();
        self::assertEquals('Mastercard', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Mastercard::getDescription
     */
    public function testGetDescription()
    {
        $output = $this->mastercardPaymentOption->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Mastercard::getGatewayCode
     */
    public function testGetGatewayCode()
    {
        $output = $this->mastercardPaymentOption->getGatewayCode();
        self::assertEquals('MASTERCARD', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Mastercard::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->mastercardPaymentOption->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Mastercard::getLogo
     */
    public function testGetLogo()
    {
        $output = $this->mastercardPaymentOption->getLogo();
        self::assertEquals('mastercard.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Mastercard::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->mastercardPaymentOption->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'MASTERCARD',
        ], $output);
    }
}
