<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Cbc;

class CbcTest extends BaseMultiSafepayTest
{

    /** @var Cbc */
    protected $cbcPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->cbcPaymentMethod = new Cbc($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Cbc::getPaymentOptionName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->cbcPaymentMethod->getName();
        self::assertEquals('CBC', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Cbc::getPaymentOptionDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->cbcPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Cbc::getPaymentOptionGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->cbcPaymentMethod->getGatewayCode();
        self::assertEquals('CBC', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Cbc::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->cbcPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Cbc::getPaymentOptionLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->cbcPaymentMethod->getLogo();
        self::assertEquals('cbc.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Cbc::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->cbcPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'CBC',
        ], $output);
    }
}
