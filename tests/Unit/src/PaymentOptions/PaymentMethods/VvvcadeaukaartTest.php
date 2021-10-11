<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Vvvcadeaukaart;

class VvvcadeaukaartTest extends BaseMultiSafepayTest
{

    /** @var Vvvcadeaukaart */
    protected $vvvcadeaukaartPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->vvvcadeaukaartPaymentMethod = new Vvvcadeaukaart($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Vvvcadeaukaart::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->vvvcadeaukaartPaymentMethod->getName();
        self::assertEquals('VVV Cadeaukaart', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Vvvcadeaukaart::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->vvvcadeaukaartPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Vvvcadeaukaart::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->vvvcadeaukaartPaymentMethod->getGatewayCode();
        self::assertEquals('VVVGIFTCRD', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Vvvcadeaukaart::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->vvvcadeaukaartPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Vvvcadeaukaart::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->vvvcadeaukaartPaymentMethod->getLogo();
        self::assertEquals('vvv.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Vvvcadeaukaart::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->vvvcadeaukaartPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'VVVGIFTCRD',
        ], $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Vvvcadeaukaart::canProcessRefunds
     */
    public function testCanProcessRefunds()
    {
        $output = $this->vvvcadeaukaartPaymentMethod->canProcessRefunds();
        self::assertFalse($output);
    }
}
