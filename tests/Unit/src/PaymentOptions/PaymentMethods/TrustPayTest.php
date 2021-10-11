<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\TrustPay;

class TrustPayTest extends BaseMultiSafepayTest
{

    /** @var TrustPay */
    protected $trustPayPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->trustPayPaymentMethod = new TrustPay($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\TrustPay::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->trustPayPaymentMethod->getName();
        self::assertEquals('TrustPay', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\TrustPay::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->trustPayPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\TrustPay::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->trustPayPaymentMethod->getGatewayCode();
        self::assertEquals('TRUSTPAY', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\TrustPay::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->trustPayPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\TrustPay::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->trustPayPaymentMethod->getLogo();
        self::assertEquals('trustpay.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\TrustPay::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->trustPayPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'TRUSTPAY',
        ], $output);
    }
}
