<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions;

use MultisafepayOfficial;
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

        $mockMultisafepay = $this->getMockBuilder(MultisafepayOfficial::class)->getMock();
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
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getDescription
     */
    public function testGetDescription()
    {
        $output = $this->idealPaymentOption->getDescription();
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getGatewayCode
     */
    public function testGetGatewayCode()
    {
        $output = $this->idealPaymentOption->getGatewayCode();
        self::assertEquals('IDEAL', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->idealPaymentOption->getTransactionType();
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getLogo
     */
    public function testGetLogo()
    {
        $output = $this->idealPaymentOption->getLogo();
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
}
