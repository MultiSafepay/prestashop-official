<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Belfius;

class BelfiusTest extends BaseMultiSafepayTest
{
    /** @var Belfius  */
    protected $belfiusPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->belfiusPaymentMethod = new Belfius($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Belfius::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->belfiusPaymentMethod->getName();
        self::assertEquals('Belfius', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Belfius::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->belfiusPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Belfius::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->belfiusPaymentMethod->getGatewayCode();
        self::assertEquals('BELFIUS', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Belfius::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->belfiusPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Belfius::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->belfiusPaymentMethod->getLogo();
        self::assertEquals('belfius.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Belfius::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->belfiusPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'BELFIUS',
        ], $output);
    }
}
