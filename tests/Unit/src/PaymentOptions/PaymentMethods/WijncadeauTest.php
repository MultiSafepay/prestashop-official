<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wijncadeau;

class WijncadeauTest extends BaseMultiSafepayTest
{

    /** @var Wijncadeau */
    protected $wijncadeauPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->wijncadeauPaymentMethod = new Wijncadeau($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wijncadeau::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->wijncadeauPaymentMethod->getName();
        self::assertEquals('Wijncadeau', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wijncadeau::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->wijncadeauPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wijncadeau::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->wijncadeauPaymentMethod->getGatewayCode();
        self::assertEquals('WIJNCADEAU', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wijncadeau::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->wijncadeauPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wijncadeau::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->wijncadeauPaymentMethod->getLogo();
        self::assertEquals('wijncadeau.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wijncadeau::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->wijncadeauPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'WIJNCADEAU',
        ], $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wijncadeau::canProcessRefunds
     */
    public function testCanProcessRefunds()
    {
        $output = $this->wijncadeauPaymentMethod->canProcessRefunds();
        self::assertFalse($output);
    }
}
