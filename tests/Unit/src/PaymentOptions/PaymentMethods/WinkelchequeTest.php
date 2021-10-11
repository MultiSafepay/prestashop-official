<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Winkelcheque;

class WinkelchequeTest extends BaseMultiSafepayTest
{

    /** @var Winkelcheque */
    protected $winkelchequeTestPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->winkelchequeTestPaymentMethod = new Winkelcheque($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Winkelcheque::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->winkelchequeTestPaymentMethod->getName();
        self::assertEquals('Winkelcheque', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Winkelcheque::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->winkelchequeTestPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Winkelcheque::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->winkelchequeTestPaymentMethod->getGatewayCode();
        self::assertEquals('WINKELCHEQUE', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Winkelcheque::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->winkelchequeTestPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Winkelcheque::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->winkelchequeTestPaymentMethod->getLogo();
        self::assertEquals('winkelcheque.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Winkelcheque::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->winkelchequeTestPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'WINKELCHEQUE',
        ], $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Winkelcheque::canProcessRefunds
     */
    public function testCanProcessRefunds()
    {
        $output = $this->winkelchequeTestPaymentMethod->canProcessRefunds();
        self::assertFalse($output);
    }
}
