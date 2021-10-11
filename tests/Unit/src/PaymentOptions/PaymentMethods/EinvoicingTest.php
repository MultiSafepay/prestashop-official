<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Einvoicing;

class EinvoicingTest extends BaseMultiSafepayTest
{
    /** @var Einvoicing */
    protected $einvoicingPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();
        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();
        $mockEinvoicing = $this->getMockBuilder(Einvoicing::class)->setConstructorArgs([$mockMultisafepay])->onlyMethods(['isDirect'])->getMock();
        $mockEinvoicing->method('isDirect')->willReturn(false);
        $this->einvoicingPaymentMethod = $mockEinvoicing;
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Einvoicing::getPaymentOptionName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->einvoicingPaymentMethod->getName();
        self::assertEquals('E-Invoicing', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Einvoicing::getPaymentOptionDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->einvoicingPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Einvoicing::getPaymentOptionGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->einvoicingPaymentMethod->getGatewayCode();
        self::assertEquals('EINVOICE', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Einvoicing::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->einvoicingPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Einvoicing::getPaymentOptionLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->einvoicingPaymentMethod->getLogo();
        self::assertEquals('einvoice.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Einvoicing::getInputFields
     */
    public function testGetDirectTransactionInputFields()
    {
        $output = $this->einvoicingPaymentMethod->getDirectTransactionInputFields();
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
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Einvoicing::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->einvoicingPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'EINVOICE',
        ], $output);
    }
}
