<?php declare(strict_types=1);

namespace MultiSafepay\Tests\Services;

use MultiSafepay\PrestaShop\Services\OrderService;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use PrestaShopCollection;

class OrderServiceTest extends BaseMultiSafepayTest
{
    /**
     * @var OrderService
     */
    protected $orderService;

    public function setUp(): void
    {
        parent::setUp();

        $this->orderService = $this->container->get('multisafepay.order_service');
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\OrderService::createPluginDetails
     */
    public function testCreatePluginDetails()
    {

        $output = $this->orderService->createPluginDetails();
        self::assertEquals(\Multisafepay::getVersion(), $output->getData()['plugin_version']);
        self::assertEquals('PrestaShop: '._PS_VERSION_, $output->getData()['shop_version']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\OrderService::getOrderTotalByOrderCollection
     */
    public function testGetOrderTotalByOrderCollection()
    {
        $fakeOrder = new \stdClass();
        $fakeOrder->total_paid = 10.54;
        $fakeOrder2 = new \stdClass();
        $fakeOrder2->total_paid = 3.21;
        $fakeOrder3 = new \stdClass();
        $fakeOrder3->total_paid = 102.99;

        $mockOrderCollection = $this->getMockBuilder(PrestaShopCollection::class)->disableOriginalConstructor()->getMock();
        $mockOrderCollection->method('getResults')->willReturn(
            [
                $fakeOrder,
                $fakeOrder2,
                $fakeOrder3
            ]
        );

        $output = $this->orderService->getOrderTotalByOrderCollection($mockOrderCollection);
        self::assertEquals(116.74, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\OrderService::getShippingTotalByOrderCollection
     */
    public function testGetShippingTotalByOrderCollection()
    {
        $fakeOrder = new \stdClass();
        $fakeOrder->total_shipping = 5;
        $fakeOrder2 = new \stdClass();
        $fakeOrder2->total_shipping = 6.99;
        $fakeOrder3 = new \stdClass();
        $fakeOrder3->total_shipping = 0;

        $mockOrderCollection = $this->getMockBuilder(PrestaShopCollection::class)->disableOriginalConstructor()->getMock();
        $mockOrderCollection->method('getResults')->willReturn(
            [
                $fakeOrder,
                $fakeOrder2,
                $fakeOrder3
            ]
        );

        $output = $this->orderService->getShippingTotalByOrderCollection($mockOrderCollection);
        self::assertEquals(11.99, $output);
    }
}
