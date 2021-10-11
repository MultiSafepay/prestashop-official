<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Webshopgiftcard;

class WebshopgiftcardTest extends BaseMultiSafepayTest
{

    /** @var Webshopgiftcard */
    protected $webshopgiftcardPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->webshopgiftcardPaymentMethod = new Webshopgiftcard($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Webshopgiftcard::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->webshopgiftcardPaymentMethod->getName();
        self::assertEquals('Webshop gift card', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Webshopgiftcard::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->webshopgiftcardPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Webshopgiftcard::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->webshopgiftcardPaymentMethod->getGatewayCode();
        self::assertEquals('WEBSHOPGIFTCARD', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Webshopgiftcard::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->webshopgiftcardPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Webshopgiftcard::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->webshopgiftcardPaymentMethod->getLogo();
        self::assertEquals('webshopgiftcard.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Webshopgiftcard::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->webshopgiftcardPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'WEBSHOPGIFTCARD',
        ], $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Webshopgiftcard::canProcessRefunds
     */
    public function testCanProcessRefunds()
    {
        $output = $this->webshopgiftcardPaymentMethod->canProcessRefunds();
        self::assertFalse($output);
    }
}
