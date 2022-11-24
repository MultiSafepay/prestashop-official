<?php declare(strict_types=1);

namespace MultiSafepay\Tests\Services;

use MultisafepayOfficial;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultiSafepay\PrestaShop\Services\SdkService;
use Order;
use Customer;
use MultiSafepay\PrestaShop\Services\RefundService;
use MultiSafepay\Tests\BaseMultiSafepayTest;

class RefundServiceTest extends BaseMultiSafepayTest
{

    /**
     * @var RefundService
     */
    protected $mockRefundService;

    public function setUp(): void
    {
        parent::setUp();
        // Mock Multisafepay class. PaymentModule
        $mockModule = $this->getMockBuilder(MultisafepayOfficial::class)->getMock();
        // Mock SdkService class
        $mockSdkService = $this->getMockBuilder(SdkService::class)->getMock();
        // Mock PaymentOptionService class
        $mockPaymentOptionService = $this->getMockBuilder(PaymentOptionService::class)->setConstructorArgs([$mockModule])->getMock();
        // Mock RefundService class
        $this->mockRefundService = $this->getMockBuilder(RefundService::class)->setConstructorArgs([$mockModule, $mockSdkService, $mockPaymentOptionService])->onlyMethods(['isVoucherRefund', 'handleMessage', 'isSplitOrder'])->getMock();
    }

    public function testGetRefundData()
    {
        $order = $this->getFixtureOrderForRefund();
        $productList = $this->getFixtureProductListForRefund();
        $output = $this->mockRefundService->getRefundData($order, $productList);
        self::assertIsArray($output);
        self::assertEquals('EUR', $output['currency']);
        self::assertEquals(14.4, $output['amount']);
    }

    public function testIsAllowedToRefundWhenOrderIsNotFromMultiSafepayModule()
    {
        $mockedOrder = $this->getFixtureOrderForRefund();
        $mockedOrder->module = 'not-multisafepay';
        $this->mockRefundService->method('isVoucherRefund')->willReturn(false);
        $output = $this->mockRefundService->isAllowedToRefund($mockedOrder, $this->getFixtureProductListForRefund());
        self::assertFalse($output);
    }

    public function testIsAllowedToRefundWhenProductListIsNotSet()
    {
        $mockedOrder = $this->getFixtureOrderForRefund();
        $this->mockRefundService->method('isVoucherRefund')->willReturn(false);
        $output = $this->mockRefundService->isAllowedToRefund($mockedOrder, null);
        self::assertFalse($output);
    }

    public function testIsAllowedToRefundWhenRefundViaVocuher()
    {
        $mockedOrder = $this->getFixtureOrderForRefund();
        $this->mockRefundService->method('isVoucherRefund')->willReturn(true);
        $output = $this->mockRefundService->isAllowedToRefund($mockedOrder, $this->getFixtureProductListForRefund());
        self::assertFalse($output);
    }

    public function testIsAllowedToRefundWhenIsSplitOrder()
    {
        $mockedOrder = $this->getFixtureOrderForRefund();
        $mockedOrder->module = 'not-multisafepay';
        $this->mockRefundService->method('isVoucherRefund')->willReturn(false);
        $this->mockRefundService->method('isSplitOrder')->willReturn(true);
        $output = $this->mockRefundService->isAllowedToRefund($mockedOrder, $this->getFixtureProductListForRefund());
        self::assertFalse($output);
    }

    public function testGetProductsRefundAmount()
    {
        $output = $this->mockRefundService->getProductsRefundAmount($this->getFixtureProductListForRefund());
        self::assertEquals('14.4', $output);
    }


    /**
     * @return Order
     */
    private function getFixtureOrderForRefund(): Order
    {
        $customerMock = $this->getMockBuilder(Customer::class)->getMock();
        $customerMock->email = 'example@multisafepay.com';
        $order = $this->getMockBuilder(Order::class)->onlyMethods(['getCustomer'])->getMock();
        $order->method('getCustomer')->willReturn($customerMock);
        $order->id     = 99;
        $order->id_currency = 1;
        $order->reference = 'XQQFHXNJS';
        $order->total_shipping = 8.470000;
        $order->round_mode = 2;
        $order->module = 'multisafepay';
        return $order;
    }

    /**
     * @return array[]
     */
    private function getFixtureProductListForRefund(): array
    {
        $randomNumber = rand(0, 100);
        return [
            $randomNumber => [
                'quantity'                => 1,
                'id_order_detail'         => $randomNumber,
                'amount'                  => 14.399,
                'unit_price'              => 14.399,
                'total_refunded_tax_incl' => 14.4,
                'total_refunded_tax_excl' => 11.9,
                'unit_price_tax_excl'     => 11.9,
                'unit_price_tax_incl'     => 14.399,
                'total_price_tax_excl'    => 11.9,
                'total_price_tax_incl'    => 14.399,
            ]
        ];
    }
}
