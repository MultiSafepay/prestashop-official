<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wellnessgiftcard;

class WellnessgiftcardTest extends BaseMultiSafepayTest
{

    /** @var Wellnessgiftcard */
    protected $wellnessgiftcardPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->wellnessgiftcardPaymentMethod = new Wellnessgiftcard($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wellnessgiftcard::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->wellnessgiftcardPaymentMethod->getName();
        self::assertEquals('Wellness gift card', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wellnessgiftcard::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->wellnessgiftcardPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wellnessgiftcard::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->wellnessgiftcardPaymentMethod->getGatewayCode();
        self::assertEquals('WELLNESSGIFTCARD', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wellnessgiftcard::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->wellnessgiftcardPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wellnessgiftcard::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->wellnessgiftcardPaymentMethod->getLogo();
        self::assertEquals('wellnessgiftcard.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wellnessgiftcard::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->wellnessgiftcardPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'WELLNESSGIFTCARD',
        ], $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Wellnessgiftcard::canProcessRefunds
     */
    public function testCanProcessRefunds()
    {
        $output = $this->wellnessgiftcardPaymentMethod->canProcessRefunds();
        self::assertFalse($output);
    }
}
