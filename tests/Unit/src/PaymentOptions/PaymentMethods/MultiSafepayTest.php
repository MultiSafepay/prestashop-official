<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay as MultiSafepayPaymentMethod;

class MultiSafepayTest extends BaseMultiSafepayTest
{
    protected $multiSafepayPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->multiSafepayPaymentMethod = new MultiSafepayPaymentMethod($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getPaymentOptionName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->multiSafepayPaymentMethod->name;
        self::assertEquals('MultiSafepay', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getPaymentOptionDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->multiSafepayPaymentMethod->description;
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getPaymentOptionGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->multiSafepayPaymentMethod->gatewayCode;
        self::assertEmpty($output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->multiSafepayPaymentMethod->type;
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getPaymentOptionLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->multiSafepayPaymentMethod->icon;
        self::assertEquals('multisafepay.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getInputFields
     */
    public function testGetInputFields()
    {
        $output = $this->multiSafepayPaymentMethod->inputs;
        self::assertIsArray($output);
        self::assertIsArray($output[0]);
        self::assertArrayHasKey('type', $output[0]);
        self::assertEquals('hidden', $output[0]['type']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->multiSafepayPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => '',
        ], $output);
    }
}
