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

namespace MultiSafepay\Tests\Builder\OrderRequest;

use Cart;
use Customer;
use Exception;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\Builder\OrderRequest\GatewayInfoBuilder;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use Order;

class GatewayInfoBuilderTest extends BaseMultiSafepayTest
{
    /**
     * @var GatewayInfoBuilder
     */
    protected $gatewayInfoBuilder;

    /**
     * Set up the test
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->gatewayInfoBuilder = new GatewayInfoBuilder();
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\GatewayInfoBuilder::build
     */
    public function testBuildWithGatewayInfo(): void
    {
        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);

        // Create a mock for the payment option with gatewayInfo
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Create a mock for GatewayInfoInterface
        $mockGatewayInfo = $this->createMock(GatewayInfoInterface::class);

        // Set up the payment option mock to return the gateway info
        $mockPaymentOption->method('getGatewayInfo')
            ->willReturn($mockGatewayInfo);

        // Create a mock order request that we can verify
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // The order request should have the gateway info added
        $mockOrderRequest->expects($this->once())
            ->method('addGatewayInfo')
            ->with($mockGatewayInfo);

        // Execute the method under test
        $this->gatewayInfoBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\GatewayInfoBuilder::build
     */
    public function testBuildWithoutGatewayInfo(): void
    {
        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);

        // Create a mock for the payment option without gatewayInfo
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockPaymentOption->method('getGatewayInfo')
            ->willReturn(null);

        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // The order request should NOT have the gateway info added
        $mockOrderRequest->expects($this->never())
            ->method('addGatewayInfo');

        // Execute the method under test
        $this->gatewayInfoBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\GatewayInfoBuilder::build
     */
    public function testBuildWithOrderParameter(): void
    {
        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockOrder = $this->createMock(Order::class);

        // Create a mock for the payment option with gatewayInfo
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Create a mock for GatewayInfoInterface
        $mockGatewayInfo = $this->createMock(GatewayInfoInterface::class);

        // Set up the payment option mock to return the gateway info
        $mockPaymentOption->method('getGatewayInfo')
            ->willReturn($mockGatewayInfo);

        // Create a mock order request that we can verify
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // The order request should have the gateway info added
        $mockOrderRequest->expects($this->once())
            ->method('addGatewayInfo')
            ->with($mockGatewayInfo);

        // Execute the method under test with the Order parameter
        $this->gatewayInfoBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
    }
}
