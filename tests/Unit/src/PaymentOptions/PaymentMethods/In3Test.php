<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\In3;

class In3Test extends BaseMultiSafepayTest
{
    /** @var AfterPay */
    protected $in3PayPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();
        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();
        $this->in3PayPaymentMethod = $this->getMockBuilder(In3::class)->setConstructorArgs([$mockMultisafepay])->onlyMethods(['isDirect'])->getMock();
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\In3::getName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->in3PayPaymentMethod->getName();
        self::assertEquals('in3', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\In3::getDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->in3PayPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\In3::getGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->in3PayPaymentMethod->getGatewayCode();
        self::assertEquals('IN3', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\In3::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->in3PayPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\In3::getLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->in3PayPaymentMethod->getLogo();
        self::assertEquals('in3.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\In3::getDirectTransactionInputFields
     */
    public function testGetDirectTransactionInputFields()
    {
        $output = $this->in3PayPaymentMethod->getDirectTransactionInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'select',
            'name'  => 'gender',
            'placeholder' => '',
            'options' => [
                [
                    'value' => 'mr',
                    'name'  => 'Mr.',
                ],
                [
                    'value' => 'mrs',
                    'name'  => 'Mrs.',
                ],
                [
                    'value' => 'miss',
                    'name'  => 'Miss',
                ],
            ],
        ], $output);
        self::assertContains([
            'type' => 'date',
            'name'  => 'birthday',
            'placeholder' => '',
            'value' => ''
        ], $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\In3::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->in3PayPaymentMethod->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'IN3',
        ], $output);
    }
}
