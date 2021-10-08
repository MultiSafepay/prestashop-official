<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\BankTransfer;

class BankTransferTest extends BaseMultiSafepayTest
{
    /** @var BankTransfer  */
    protected $bankTransferPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->bankTransferPaymentMethod = $this->getMockBuilder(BankTransfer::class)->setConstructorArgs([$multisafepay])->onlyMethods(['isDirect'])->getMock();
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\BankTransfer::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->bankTransferPaymentMethod->getName();
        self::assertEquals('Bank Transfer', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\BankTransfer::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->bankTransferPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\BankTransfer::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->bankTransferPaymentMethod->getGatewayCode();
        self::assertEquals('BANKTRANS', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\BankTransfer::getTransactionType
     */
    public function testGetTransactionTypeAsRedirect()
    {
        $this->bankTransferPaymentMethod->method('isDirect')->willReturn(false);
        $output = $this->bankTransferPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\BankTransfer::getTransactionType
     */
    public function testGetTransactionTypeAsDirect()
    {
        $this->bankTransferPaymentMethod->method('isDirect')->willReturn(true);
        $output = $this->bankTransferPaymentMethod->getTransactionType();
        self::assertEquals('direct', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\BankTransfer::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->bankTransferPaymentMethod->getLogo();
        self::assertEquals('banktrans.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\BankTransfer::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->bankTransferPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'BANKTRANS',
        ], $output);
    }
}
