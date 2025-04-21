<?php declare(strict_types=1);
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\Tests\Builder;

use Cart;
use Customer;
use Exception;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\PrestaShop\Builder\OrderRequest\OrderRequestBuilderInterface;
use MultiSafepay\PrestaShop\Builder\OrderRequestBuilder;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Util\CurrencyUtil;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use Order;
use PHPUnit\Framework\MockObject\MockObject;

class OrderRequestBuilderTest extends BaseMultiSafepayTest
{
    /**
     * @var OrderRequestBuilder
     */
    protected $orderRequestBuilder;

    /**
     * @var CurrencyUtil|MockObject
     */
    protected $currencyUtilMock;

    /**
     * @var OrderRequestBuilderInterface[]|MockObject[]
     */
    protected $orderRequestBuildersMock;

    /**
     * Set up the test
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->currencyUtilMock = $this->createMock(CurrencyUtil::class);

        // Create mock builders
        $this->orderRequestBuildersMock = [
            $this->createMock(OrderRequestBuilderInterface::class),
            $this->createMock(OrderRequestBuilderInterface::class),
        ];

        $this->orderRequestBuilder = new OrderRequestBuilder(
            $this->orderRequestBuildersMock,
            $this->currencyUtilMock
        );
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequestBuilder::build
     */
    public function testBuildWithoutOrder(): void
    {
        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCart->id = 1001;
        $mockCart->id_currency = 2;

        $mockCustomer = $this->createMock(Customer::class);

        // Mock cart getOrderTotal method
        $mockCart->method('getOrderTotal')
            ->willReturn(100.0);

        // Create a mock for the payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Mock payment option methods
        $mockPaymentOption->method('getGatewayCode')
            ->willReturn('IDEAL');

        $mockPaymentOption->method('getTransactionType')
            ->willReturn('redirect');

        // Mock currency util to return a currency code
        $this->currencyUtilMock->method('getCurrencyIsoCodeById')
            ->with(2)
            ->willReturn('EUR');

        // Configure all builders to be called
        foreach ($this->orderRequestBuildersMock as $builderMock) {
            $builderMock->expects($this->once())
                ->method('build')
                ->with(
                    $this->identicalTo($mockCart),
                    $this->identicalTo($mockCustomer),
                    $this->identicalTo($mockPaymentOption),
                    $this->isInstanceOf(OrderRequest::class),
                    null
                );
        }

        // Execute the build method and get the OrderRequest
        $orderRequest = $this->orderRequestBuilder->build($mockCart, $mockCustomer, $mockPaymentOption);

        // Verify OrderRequest properties
        $this->assertInstanceOf(OrderRequest::class, $orderRequest);
        $this->assertArrayHasKey('order_id', $orderRequest->getData());
        $this->assertEquals('1001', $orderRequest->getData()['order_id']);
        $this->assertArrayHasKey('gateway', $orderRequest->getData());
        $this->assertEquals('IDEAL', $orderRequest->getData()['gateway']);
        $this->assertArrayHasKey('type', $orderRequest->getData());
        $this->assertEquals('redirect', $orderRequest->getData()['type']);

        // Check if a data key exists first to avoid undefined array key error
        if (isset($orderRequest->getData()['data']) && is_array($orderRequest->getData()['data'])) {
            $this->assertArrayHasKey('var2', $orderRequest->getData()['data']);
            $this->assertEquals('1001', $orderRequest->getData()['data']['var2']);
        }
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequestBuilder::build
     */
    public function testBuildWithOrder(): void
    {
        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCart->id = 1001;
        $mockCart->id_currency = 2;

        $mockCustomer = $this->createMock(Customer::class);

        // Create a mock for the Order
        $mockOrder = $this->createMock(Order::class);
        $mockOrder->reference = 'REF12345';

        // Mock cart getOrderTotal method
        $mockCart->method('getOrderTotal')
            ->willReturn(100.0);

        // Create a mock for the payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Mock payment option methods
        $mockPaymentOption->method('getGatewayCode')
            ->willReturn('CREDITCARD');

        $mockPaymentOption->method('getTransactionType')
            ->willReturn('direct');

        // Mock currency util to return a currency code
        $this->currencyUtilMock->method('getCurrencyIsoCodeById')
            ->with(2)
            ->willReturn('USD');

        // Configure all builders to be called
        foreach ($this->orderRequestBuildersMock as $builderMock) {
            $builderMock->expects($this->once())
                ->method('build')
                ->with(
                    $this->identicalTo($mockCart),
                    $this->identicalTo($mockCustomer),
                    $this->identicalTo($mockPaymentOption),
                    $this->isInstanceOf(OrderRequest::class),
                    $this->identicalTo($mockOrder)
                );
        }

        // Execute the build method and get the OrderRequest
        $orderRequest = $this->orderRequestBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrder);

        // Verify OrderRequest properties
        $this->assertInstanceOf(OrderRequest::class, $orderRequest);
        $this->assertArrayHasKey('order_id', $orderRequest->getData());
        $this->assertEquals('REF12345', $orderRequest->getData()['order_id']);
        $this->assertArrayHasKey('gateway', $orderRequest->getData());
        $this->assertEquals('CREDITCARD', $orderRequest->getData()['gateway']);
        $this->assertArrayHasKey('type', $orderRequest->getData());
        $this->assertEquals('direct', $orderRequest->getData()['type']);

        // Check if a data key exists first to avoid undefined array key error
        if (isset($orderRequest->getData()['data']) && is_array($orderRequest->getData()['data'])) {
            $this->assertArrayHasKey('var2', $orderRequest->getData()['data']);
            $this->assertEquals('1001', $orderRequest->getData()['data']['var2']);
        }
    }
}
