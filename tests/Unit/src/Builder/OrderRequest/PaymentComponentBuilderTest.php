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
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\PrestaShop\Builder\OrderRequest\PaymentComponentBuilder;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use Order;

class PaymentComponentBuilderTest extends BaseMultiSafepayTest
{
    /**
     * @var PaymentComponentBuilder
     */
    protected $paymentComponentBuilder;

    /**
     * Set up the test
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->paymentComponentBuilder = new PaymentComponentBuilder();
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\PaymentComponentBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBuildWithoutPaymentComponentAllowed(): void
    {
        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Mock dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockOrder = $this->createMock(Order::class);

        // Create mock payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set payment option to not allow payment component
        $mockPaymentOption->method('allowPaymentComponent')
            ->willReturn(false);

        // OrderRequest should not have methods called
        $mockOrderRequest->expects($this->never())->method('addData');
        $mockOrderRequest->expects($this->never())->method('addType');

        // Execute test
        $this->paymentComponentBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\PaymentComponentBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBuildWithPaymentComponentAllowed(): void
    {
        // Create a testable subclass that overrides the getPayload method
        $testableBuilder = new class extends PaymentComponentBuilder {
            private $mockPayload = 'fake-payload';

            protected function getPayload(): ?string
            {
                return $this->mockPayload;
            }
        };

        // Use our testable builder for this test
        $this->paymentComponentBuilder = $testableBuilder;

        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Mock dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockOrder = $this->createMock(Order::class);

        // Create mock payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set payment option to allow payment component
        $mockPaymentOption->method('allowPaymentComponent')
            ->willReturn(true);

        // OrderRequest should have methods called with specific parameters
        $mockOrderRequest->expects($this->once())
            ->method('addData')
            ->with(['payment_data' => ['payload' => 'fake-payload']]);

        $mockOrderRequest->expects($this->once())
            ->method('addType')
            ->with('direct');

        // Execute test
        $this->paymentComponentBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\PaymentComponentBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBuildWithPaymentComponentAllowedEmptyPayload(): void
    {
        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Mock dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockOrder = $this->createMock(Order::class);

        // Create mock payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set payment option to not allow payment component
        $mockPaymentOption->method('allowPaymentComponent')
            ->willReturn(true);

        // OrderRequest should not have methods called
        $mockOrderRequest->expects($this->never())->method('addData');
        $mockOrderRequest->expects($this->never())->method('addType');

        // Execute test
        $this->paymentComponentBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
    }
}
