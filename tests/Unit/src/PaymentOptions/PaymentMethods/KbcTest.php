<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Kbc;

class KbcTest extends BaseMultiSafepayTest
{

    /** @var Kbc */
    protected $kbcPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->kbcPaymentMethod = new Kbc($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Kbc::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->kbcPaymentMethod->getName();
        self::assertEquals('KBC', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Kbc::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->kbcPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Kbc::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->kbcPaymentMethod->getGatewayCode();
        self::assertEquals('KBC', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Kbc::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->kbcPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Kbc::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->kbcPaymentMethod->getLogo();
        self::assertEquals('kbc.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Kbc::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->kbcPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'KBC',
        ], $output);
    }
}
