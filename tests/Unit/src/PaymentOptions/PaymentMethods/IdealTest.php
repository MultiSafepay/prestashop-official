<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use Multisafepay;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal;
use MultiSafepay\PrestaShop\Services\IssuerService;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use Configuration;

class IdealTest extends BaseMultiSafepayTest
{
    protected $idealPaymentOption;

    public function setUp(): void
    {
        parent::setUp();

        $mockIssuerService = $this->getMockBuilder(IssuerService::class)->disableOriginalConstructor()->getMock();
        $mockIssuerService->method('getIssuers')->willReturn(
            [
                'value' => 1234,
                'name'  => 'Test Issuer',
            ]
        );

        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();
        $mockMultisafepay->method('get')->with('multisafepay.issuer_service')->willReturn(
            $mockIssuerService
        );

        $mockIdeal = $this->getMockBuilder(Ideal::class)->setConstructorArgs([$mockMultisafepay])->onlyMethods(['isDirect', 'allowTokenization'])->getMock();
        $mockIdeal->method('isDirect')->willReturn(
            false
        );
        $mockIdeal->method('allowTokenization')->willReturn(
            false
        );

        $this->idealPaymentOption = $mockIdeal;
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->idealPaymentOption->name;
        self::assertEquals('iDEAL', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->idealPaymentOption->description;
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->idealPaymentOption->gatewayCode;
        self::assertEquals('IDEAL', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->idealPaymentOption->type;
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->idealPaymentOption->icon;
        self::assertEquals('ideal.png', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getDirectTransactionInputFields
     */
    public function testGetInputFields()
    {
        $output = $this->idealPaymentOption->getDirectTransactionInputFields();
        self::assertIsArray($output);
        self::assertCount(1, $output);
        self::assertArrayHasKey('type', $output[0]);
        self::assertEquals('select', $output[0]['type']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getInputFields
     */
    public function testGetHiddenGatewayField()
    {
        $output = $this->idealPaymentOption->getInputFields();
        self::assertIsArray($output);
        self::assertContains([
            'type' => 'hidden',
            'name'  => 'gateway',
            'value' => 'IDEAL',
        ], $output);
    }
}
