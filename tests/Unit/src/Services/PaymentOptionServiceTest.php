<?php declare(strict_types=1);

namespace MultiSafepay\Tests\Services;

use Multisafepay;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Services\IssuerService;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultiSafepay\PrestaShop\Services\TokenizationService;
use MultiSafepay\Tests\BaseMultiSafepayTest;

class PaymentOptionServiceTest extends BaseMultiSafepayTest
{
    protected $paymentOptionsService;

    protected $mockIssuerService;

    protected $mockTokenizationService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mockIssuerService = $this->getMockBuilder(IssuerService::class)->disableOriginalConstructor()->getMock();
        $this->mockIssuerService->method('getIssuers')->willReturn(
            [
                'value' => 1234,
                'name'  => 'Test Issuer',
            ]
        );

        $this->mockTokenizationService = $this->getMockBuilder(TokenizationService::class)->disableOriginalConstructor()->getMock();
        $this->mockTokenizationService->method('createTokenizationCheckoutFields')->willReturn(
            []
        );

        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();
        $mockMultisafepay->method('get')->willReturnCallback([$this, 'multisafepayGetCallback']);

        $this->paymentOptionsService = new PaymentOptionService($mockMultisafepay);
    }

    public function multisafepayGetCallback()
    {
        $args = func_get_args();

        if ('multisafepay.issuer_service' === $args[0]) {
            return $this->mockIssuerService;
        }

        if ('multisafepay.tokenization_service' === $args[0]) {
            return $this->mockTokenizationService;
        }

        return null;
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\PaymentOptionService::getMultiSafepayPaymentOptions
     */
    public function testGetMultiSafepayPaymentOptionsReturnArray(): void
    {
        $output = $this->paymentOptionsService->getMultiSafepayPaymentOptions();
        self::assertIsArray($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\PaymentOptionService::getMultiSafepayPaymentOptions
     */
    public function testGetMultiSafepayPaymentOptionsReturnInstanceOfBasePaymentOption(): void
    {
        $paymentOptions = $this->paymentOptionsService->getMultiSafepayPaymentOptions();
        foreach ($paymentOptions as $paymentOption) {
            self::assertInstanceOf(BasePaymentOption::class, $paymentOption);
        }
    }
}
