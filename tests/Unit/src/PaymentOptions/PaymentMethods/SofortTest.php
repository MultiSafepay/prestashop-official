<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Sofort;

class SofortTest extends BaseMultiSafepayTest
{

    /** @var Sofort */
    protected $sofortPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->sofortPaymentMethod = new Sofort($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Sofort::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->sofortPaymentMethod->getName();
        self::assertEquals('Sofort', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Sofort::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->sofortPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Sofort::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->sofortPaymentMethod->getGatewayCode();
        self::assertEquals('DIRECTBANK', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Sofort::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->sofortPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Sofort::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->sofortPaymentMethod->getLogo();
        self::assertEquals('sofort.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Sofort::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->sofortPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'DIRECTBANK',
        ], $output);
    }
}
