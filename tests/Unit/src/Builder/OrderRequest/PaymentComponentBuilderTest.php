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
     * @var TestPaymentComponentBuilder
     */
    protected $paymentComponentBuilder;

    /**
     * @var PaymentComponentBuilder
     */
    protected $originalPaymentComponentBuilder;

    /**
     * Set up the test
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->paymentComponentBuilder = new TestPaymentComponentBuilder();
        $this->originalPaymentComponentBuilder = new PaymentComponentBuilder();
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\PaymentComponentBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBuildWithoutPaymentComponentAllowed(): void
    {
        // Configure the test instance
        $this->paymentComponentBuilder->setPayload(null);

        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockOrder = $this->createMock(Order::class);

        // Create a mock for the payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set the payment option to not allow the payment component
        $mockPaymentOption->method('allowPaymentComponent')
            ->willReturn(false);

        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // The order request should NOT have addData nor addType called
        $mockOrderRequest->expects($this->never())
            ->method('addData');

        $mockOrderRequest->expects($this->never())
            ->method('addType');

        // Execute the method under test
        $this->paymentComponentBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\PaymentComponentBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBuildWithEmptyPayload(): void
    {
        // Configure the test instance
        $this->paymentComponentBuilder->setPayload('');

        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockOrder = $this->createMock(Order::class);

        // Create a mock for the payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set payment option to allow payment component
        $mockPaymentOption->method('allowPaymentComponent')
            ->willReturn(true);

        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // The order request should NOT have addData nor addType called
        $mockOrderRequest->expects($this->never())
            ->method('addData');

        $mockOrderRequest->expects($this->never())
            ->method('addType');

        // Execute the method under test
        $this->paymentComponentBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\PaymentComponentBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBuildWithValidPayload(): void
    {
        // Configure the test instance with a valid payload
        $testPayload = 'valid-test-payload';
        $this->paymentComponentBuilder->setPayload($testPayload);

        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockOrder = $this->createMock(Order::class);

        // Create a mock for the payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set payment option to allow payment component
        $mockPaymentOption->method('allowPaymentComponent')
            ->willReturn(true);

        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // The order request should have addData and addType called
        $mockOrderRequest->expects($this->once())
            ->method('addData')
            ->with(['payment_data' => ['payload' => $testPayload]]);

        $mockOrderRequest->expects($this->once())
            ->method('addType')
            ->with(OrderRequest::DIRECT_TYPE);

        // Execute the method under test
        $this->paymentComponentBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
    }
}
