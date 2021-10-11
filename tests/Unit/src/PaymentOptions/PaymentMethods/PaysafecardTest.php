<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Paysafecard;

class PaysafecardTest extends BaseMultiSafepayTest
{

    /** @var Paysafecard */
    protected $paysafecardPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->paysafecardPaymentMethod = new Paysafecard($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Paysafecard::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->paysafecardPaymentMethod->getName();
        self::assertEquals('Paysafecard', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Paysafecard::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->paysafecardPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Paysafecard::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->paysafecardPaymentMethod->getGatewayCode();
        self::assertEquals('PSAFECARD', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Paysafecard::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->paysafecardPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Paysafecard::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->paysafecardPaymentMethod->getLogo();
        self::assertEquals('paysafecard.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Paysafecard::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->paysafecardPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'PSAFECARD',
        ], $output);
    }
}
