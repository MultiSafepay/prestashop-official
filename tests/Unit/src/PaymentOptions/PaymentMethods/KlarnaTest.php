<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Klarna;

class KlarnaTest extends BaseMultiSafepayTest
{
    /** @var Klarna  */
    protected $klarnaPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->klarnaPaymentMethod = new Klarna($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Klarna::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->klarnaPaymentMethod->getName();
        self::assertEquals('Klarna - Pay in 30 days', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Klarna::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->klarnaPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Klarna::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->klarnaPaymentMethod->getGatewayCode();
        self::assertEquals('KLARNA', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Klarna::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->klarnaPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Klarna::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->klarnaPaymentMethod->getLogo();
        self::assertEquals('klarna.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Klarna::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->klarnaPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'KLARNA',
        ], $output);
    }
}
