<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Dotpay;

class DotpayTest extends BaseMultiSafepayTest
{

    /** @var Dotpay */
    protected $dotpayPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->dotpayPaymentMethod = new Dotpay($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Dotpay::getPaymentOptionName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->dotpayPaymentMethod->getName();
        self::assertEquals('Dotpay', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Dotpay::getPaymentOptionDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->dotpayPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Dotpay::getPaymentOptionGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->dotpayPaymentMethod->getGatewayCode();
        self::assertEquals('DOTPAY', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Dotpay::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->dotpayPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Dotpay::getPaymentOptionLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->dotpayPaymentMethod->getLogo();
        self::assertEquals('dotpay.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Dotpay::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->dotpayPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'DOTPAY',
        ], $output);
    }
}
