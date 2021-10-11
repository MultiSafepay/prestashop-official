<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\YourGift;

class YourGiftTest extends BaseMultiSafepayTest
{

    /** @var YourGift */
    protected $yourGiftPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->yourGiftPaymentMethod = new YourGift($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\YourGift::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->yourGiftPaymentMethod->getName();
        self::assertEquals('YourGift', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\YourGift::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->yourGiftPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Yourgift::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->yourGiftPaymentMethod->getGatewayCode();
        self::assertEquals('YOURGIFT', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Yourgift::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->yourGiftPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\YourGift::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->yourGiftPaymentMethod->getLogo();
        self::assertEquals('yourgift.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\YourGift::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->yourGiftPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'YOURGIFT',
        ], $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\YourGift::canProcessRefunds
     */
    public function testCanProcessRefunds()
    {
        $output = $this->yourGiftPaymentMethod->canProcessRefunds();
        self::assertFalse($output);
    }
}
