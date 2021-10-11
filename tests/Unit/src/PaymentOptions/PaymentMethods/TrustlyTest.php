<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Trustly;

class TrustlyTest extends BaseMultiSafepayTest
{

    /** @var Trustly */
    protected $trustlyPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->trustlyPaymentMethod = new Trustly($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Trustly::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->trustlyPaymentMethod->getName();
        self::assertEquals('Trustly', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Trustly::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->trustlyPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Trustly::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->trustlyPaymentMethod->getGatewayCode();
        self::assertequals('TRUSTLY', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Trustly::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->trustlyPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Trustly::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->trustlyPaymentMethod->getLogo();
        self::assertEquals('trustly.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Trustly::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->trustlyPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'TRUSTLY',
        ], $output);
    }
}
