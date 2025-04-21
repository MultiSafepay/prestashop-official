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
use MultiSafepay\PrestaShop\Builder\OrderRequest\TokenizationBuilder;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use Order;

class TokenizationBuilderTest extends BaseMultiSafepayTest
{
    /**
     * @var TestTokenizationBuilder
     */
    protected $tokenizationBuilder;

    /**
     * @var TokenizationBuilder
     */
    protected $originalTokenizationBuilder;

    /**
     * Set up the test
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->tokenizationBuilder = new TestTokenizationBuilder();
        $this->originalTokenizationBuilder = new TokenizationBuilder();
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\TokenizationBuilder::build
     */
    public function testBuildWhenTokenizationNotAllowed(): void
    {
        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockOrder = $this->createMock(Order::class);

        // Create a mock for the payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set payment option to not allow tokenization
        $mockPaymentOption->method('allowTokenization')
            ->willReturn(false);

        $mockPaymentOption->method('allowPaymentComponent')
            ->willReturn(false);

        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // The order request should not have any methods called
        $mockOrderRequest->expects($this->never())
            ->method('addRecurringModel');

        $mockOrderRequest->expects($this->never())
            ->method('addRecurringId');

        $mockOrderRequest->expects($this->never())
            ->method('addType');

        // Execute the method under test (using the original implementation)
        $this->originalTokenizationBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\TokenizationBuilder::build
     */
    public function testBuildWhenTokenizationAllowedButWithPaymentComponent(): void
    {
        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockOrder = $this->createMock(Order::class);

        // Create a mock for the payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set the payment option to allow tokenization but also allow the payment component
        $mockPaymentOption->method('allowTokenization')
            ->willReturn(true);

        $mockPaymentOption->method('allowPaymentComponent')
            ->willReturn(true);

        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // The order request should not have any methods called
        $mockOrderRequest->expects($this->never())
            ->method('addRecurringModel');

        $mockOrderRequest->expects($this->never())
            ->method('addRecurringId');

        $mockOrderRequest->expects($this->never())
            ->method('addType');

        // Execute the method under test (using the original implementation)
        $this->originalTokenizationBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\TokenizationBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBuildWhenTokenizationAllowedAndShouldSaveToken(): void
    {
        // Configure the test instance
        $this->tokenizationBuilder->setShouldSaveToken(true);
        $this->tokenizationBuilder->setToken(null);

        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockOrder = $this->createMock(Order::class);

        // Create a mock for the payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set payment option to allow tokenization but not payment component
        $mockPaymentOption->method('allowTokenization')
            ->willReturn(true);

        $mockPaymentOption->method('allowPaymentComponent')
            ->willReturn(false);

        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // For this test we expect recurring model to be added
        $mockOrderRequest->expects($this->once())
            ->method('addRecurringModel')
            ->with('cardOnFile')
            ->willReturnSelf();

        // Execute the method under test (using our test implementation)
        $this->tokenizationBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\TokenizationBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBuildWhenTokenizationAllowedWithValidToken(): void
    {
        // Configure the test instance
        $this->tokenizationBuilder->setShouldSaveToken(false);
        $this->tokenizationBuilder->setToken('test-token-123');

        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockOrder = $this->createMock(Order::class);

        // Create a mock for the payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set payment option to allow tokenization but not payment component
        $mockPaymentOption->method('allowTokenization')
            ->willReturn(true);

        $mockPaymentOption->method('allowPaymentComponent')
            ->willReturn(false);

        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set up the chain of expected method calls
        $mockOrderRequest->expects($this->once())
            ->method('addRecurringModel')
            ->with('cardOnFile')
            ->willReturnSelf();

        $mockOrderRequest->expects($this->once())
            ->method('addRecurringId')
            ->with('test-token-123')
            ->willReturnSelf();

        $mockOrderRequest->expects($this->once())
            ->method('addType')
            ->with(OrderRequest::DIRECT_TYPE)
            ->willReturnSelf();

        // Execute the method under test (using our test implementation)
        $this->tokenizationBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\TokenizationBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBuildWhenTokenizationAllowedWithNewToken(): void
    {
        // Configure the test instance
        $this->tokenizationBuilder->setShouldSaveToken(true);
        $this->tokenizationBuilder->setToken('new');

        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockOrder = $this->createMock(Order::class);

        // Create a mock for the payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set payment option to allow tokenization but not payment component
        $mockPaymentOption->method('allowTokenization')
            ->willReturn(true);

        $mockPaymentOption->method('allowPaymentComponent')
            ->willReturn(false);

        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // We're testing a 'new' token, so addRecurringModel should be called,
        // but the other methods should not be called
        $mockOrderRequest->expects($this->once())
            ->method('addRecurringModel')
            ->with('cardOnFile')
            ->willReturnSelf();

        $mockOrderRequest->expects($this->never())
            ->method('addRecurringId');

        $mockOrderRequest->expects($this->never())
            ->method('addType');

        // Execute the method under test (using our test implementation)
        $this->tokenizationBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
    }
}
