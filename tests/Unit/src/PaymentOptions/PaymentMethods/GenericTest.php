<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic;
use MultiSafepay\Tests\BaseMultiSafepayTest;

class GenericTest extends BaseMultiSafepayTest
{
    protected $genericPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->genericPaymentMethod = new Generic($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getName
     */
    public function testGetName()
    {
        $output = $this->genericPaymentMethod->getName();
        self::assertEquals('Generic Gateway', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getDescription
     */
    public function testGetDescription()
    {
        $output = $this->genericPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getGatewayCode
     */
    public function testGetGatewayCode()
    {
        $output = $this->genericPaymentMethod->getGatewayCode();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->genericPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getLogo
     */
    public function testGetLogo()
    {
        $output = $this->genericPaymentMethod->getLogo();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getInputFields
     */
    public function testGetInputFields()
    {
        $output = $this->genericPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertIsArray($output[0]);
        self::assertArrayHasKey('type', $output[0]);
        self::assertEquals('hidden', $output[0]['type']);
    }
}
