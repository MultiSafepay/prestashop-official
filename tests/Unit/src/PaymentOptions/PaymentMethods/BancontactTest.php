<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Bancontact;

class BancontactTest extends BaseMultiSafepayTest
{
    /** @var Bancontact  */
    protected $bancontactPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->bancontactPaymentMethod = new Bancontact($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Bancontact::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->bancontactPaymentMethod->getName();
        self::assertEquals('Bancontact', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Bancontact::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->bancontactPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Bancontact::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->bancontactPaymentMethod->getGatewayCode();
        self::assertEquals('MISTERCASH', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Bancontact::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->bancontactPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Bancontact::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->bancontactPaymentMethod->getLogo();
        self::assertEquals('bancontact.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Bancontact::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->bancontactPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'MISTERCASH',
        ], $output);
    }
}
