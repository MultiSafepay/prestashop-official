<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\RequestToPay;

class RequestToPayTest extends BaseMultiSafepayTest
{

    /** @var RequestToPay */
    protected $requestToPayPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->requestToPayPaymentMethod = new RequestToPay($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\RequestToPay::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->requestToPayPaymentMethod->getName();
        self::assertEquals('Request to Pay powered by Deutsche Bank', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\RequestToPay::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->requestToPayPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\RequestToPay::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->requestToPayPaymentMethod->getGatewayCode();
        self::assertEquals('DBRTP', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\RequestToPay::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->requestToPayPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\RequestToPay::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->requestToPayPaymentMethod->getLogo();
        self::assertEquals('dbrtp.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\RequestToPay::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->requestToPayPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'DBRTP',
        ], $output);
    }
}
