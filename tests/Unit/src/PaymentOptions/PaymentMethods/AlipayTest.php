<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Alipay;

class AlipayTest extends BaseMultiSafepayTest
{
    /** @var Alipay  */
    protected $alipayPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->alipayPaymentMethod = new Alipay($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Alipay::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->alipayPaymentMethod->getName();
        self::assertEquals('Alipay', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Alipay::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->alipayPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Alipay::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->alipayPaymentMethod->getGatewayCode();
        self::assertEquals('ALIPAY', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Alipay::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->alipayPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Alipay::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->alipayPaymentMethod->getLogo();
        self::assertEquals('alipay.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Alipay::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->alipayPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'ALIPAY',
        ], $output);
    }
}
