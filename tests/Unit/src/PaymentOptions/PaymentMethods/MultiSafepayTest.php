<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use MultisafepayOfficial;
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

        $mockMultiSafepay = $this->getMockBuilder(MultiSafepayPaymentMethod::class)->setConstructorArgs([$multisafepay])->onlyMethods(['allowTokenization'])->getMock();
        $mockMultiSafepay->method('allowTokenization')->willReturn(
            false
        );
        $this->multiSafepayPaymentMethod = $mockMultiSafepay;
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getDescription
     */
    public function testGetDescription()
    {
        $output = $this->multiSafepayPaymentMethod->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getGatewayCode
     */
    public function testGetGatewayCode()
    {
        $output = $this->multiSafepayPaymentMethod->getGatewayCode();
        self::assertEmpty($output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->multiSafepayPaymentMethod->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getLogo
     */
    public function testGetLogo()
    {
        $output = $this->multiSafepayPaymentMethod->getLogo();
        self::assertEquals('multisafepay.png', $output);
        self::assertIsString($output);
    }
}
