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
use MultiSafepay\PrestaShop\Builder\OrderRequest\DescriptionBuilder;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use Order;

class DescriptionBuilderTest extends BaseMultiSafepayTest
{
    /**
     * @var DescriptionBuilder
     */
    protected $descriptionBuilder;

    /**
     * Set up the test
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->descriptionBuilder = new DescriptionBuilder();
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\DescriptionBuilder::build
     */
    public function testBuildWithoutOrder(): void
    {
        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCart->id = 1001;

        $mockCustomer = $this->createMock(Customer::class);

        // Create a mock for the payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // The order request should have addDescriptionText called
        $mockOrderRequest->expects($this->once())
            ->method('addDescriptionText')
            ->with($this->anything());

        // Execute the method under test without an order
        $this->descriptionBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\DescriptionBuilder::build
     */
    public function testBuildWithOrder(): void
    {
        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);

        // Create a mock for the Order
        $mockOrder = $this->createMock(Order::class);
        $mockOrder->reference = 'REF12345';

        // Create a mock for the payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // The order request should have addDescriptionText called
        $mockOrderRequest->expects($this->once())
            ->method('addDescriptionText')
            ->with($this->anything());

        // Execute the method under test with Order
        $this->descriptionBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
    }
}
