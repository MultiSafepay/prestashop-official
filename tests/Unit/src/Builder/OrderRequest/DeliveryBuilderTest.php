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

use Address;
use Cart;
use Customer;
use Exception;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\PrestaShop\Builder\OrderRequest\CustomerBuilder\AddressBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\DeliveryBuilder;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Util\AddressUtil;
use MultiSafepay\PrestaShop\Util\CustomerUtil;
use MultiSafepay\PrestaShop\Util\LanguageUtil;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\ValueObject\Customer\Address as SdkAddress;
use Order;
use PHPUnit\Framework\MockObject\MockObject;

class DeliveryBuilderTest extends BaseMultiSafepayTest
{
    /**
     * @var DeliveryBuilder
     */
    protected $deliveryBuilder;

    /**
     * @var AddressBuilder|MockObject
     */
    protected $addressBuilderMock;

    /**
     * @var AddressUtil|MockObject
     */
    protected $addressUtilMock;

    /**
     * @var CustomerUtil|MockObject
     */
    protected $customerUtilMock;

    /**
     * @var LanguageUtil|MockObject
     */
    protected $languageUtilMock;

    /**
     * Set up the test
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->addressBuilderMock = $this->createMock(AddressBuilder::class);
        $this->addressUtilMock = $this->createMock(AddressUtil::class);
        $this->customerUtilMock = $this->createMock(CustomerUtil::class);
        $this->languageUtilMock = $this->createMock(LanguageUtil::class);

        $this->deliveryBuilder = new DeliveryBuilder(
            $this->addressBuilderMock,
            $this->addressUtilMock,
            $this->customerUtilMock,
            $this->languageUtilMock
        );
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\DeliveryBuilder::build
     */
    public function testBuildWithNoShippingCost(): void
    {
        // Mock the dependencies
        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockOrder = $this->createMock(Order::class);

        // Make the cart return 0 shipping cost
        $mockCart->method('getTotalShippingCost')
            ->willReturn(0);

        // Create a mock for the payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // The order request should NOT have addDelivery called
        $mockOrderRequest->expects($this->never())
            ->method('addDelivery');

        // Execute the method under test
        $this->deliveryBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\DeliveryBuilder::build
     */
    public function testBuildWithShippingCost(): void
    {
        // Mock cart with delivery address and shipping cost
        $mockCart = $this->createMock(Cart::class);
        $mockCart->id_address_delivery = 5;
        $mockCart->id_lang = 2;

        $mockCart->method('getTotalShippingCost')
            ->willReturn(10.0);

        // Mock customer
        $mockCustomer = $this->createMock(Customer::class);
        $mockCustomer->email = 'test@example.com';

        // Mock order
        $mockOrder = $this->createMock(Order::class);

        // Mock address from PrestaShop
        $mockPrestashopAddress = $this->createMock(Address::class);
        $mockPrestashopAddress->phone = '123456789';
        $mockPrestashopAddress->firstname = 'John';
        $mockPrestashopAddress->lastname = 'Doe';
        $mockPrestashopAddress->company = 'Test Company';

        // Mock the SDK Address object
        $mockSdkAddress = $this->createMock(SdkAddress::class);

        // Mock SDK customer details
        $mockCustomerDetails = $this->createMock(CustomerDetails::class);

        // Configure address util to return our mock address
        $this->addressUtilMock->expects($this->once())
            ->method('getAddress')
            ->with(5)
            ->willReturn($mockPrestashopAddress);

        // Configure address builder to return our mock SDK address
        $this->addressBuilderMock->expects($this->once())
            ->method('build')
            ->with($mockPrestashopAddress)
            ->willReturn($mockSdkAddress);

        // Configure language util to return a language code
        $this->languageUtilMock->expects($this->once())
            ->method('getLanguageCode')
            ->with(2)
            ->willReturn('es');

        // Configure customer util to create a customer object
        $this->customerUtilMock->expects($this->once())
            ->method('createCustomer')
            ->with(
                $mockSdkAddress,
                'test@example.com',
                '123456789',
                'John',
                'Doe',
                $this->anything(),
                $this->anything(),
                'es',
                'Test Company'
            )
            ->willReturn($mockCustomerDetails);

        // Create a mock for the payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // The order request should have addDelivery called with our customer details
        $mockOrderRequest->expects($this->once())
            ->method('addDelivery')
            ->with($mockCustomerDetails);

        // Create server globals
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';

        // Execute the method under test
        $this->deliveryBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);

        // Cleanup server globals
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\DeliveryBuilder::build
     */
    public function testBuildWithoutServerGlobals(): void
    {
        // Mock cart with delivery address and shipping cost
        $mockCart = $this->createMock(Cart::class);
        $mockCart->id_address_delivery = 5;
        $mockCart->id_lang = 2;

        $mockCart->method('getTotalShippingCost')
            ->willReturn(10.0);

        // Mock customer
        $mockCustomer = $this->createMock(Customer::class);
        $mockCustomer->email = 'test@example.com';

        // Mock address from PrestaShop
        $mockPrestashopAddress = $this->createMock(Address::class);
        $mockPrestashopAddress->phone = '123456789';
        $mockPrestashopAddress->firstname = 'John';
        $mockPrestashopAddress->lastname = 'Doe';
        $mockPrestashopAddress->company = 'Test Company';

        // Mock the SDK Address object
        $mockSdkAddress = $this->createMock(SdkAddress::class);

        // Mock SDK customer details
        $mockCustomerDetails = $this->createMock(CustomerDetails::class);

        // Configure address util to return our mock address
        $this->addressUtilMock->expects($this->once())
            ->method('getAddress')
            ->with(5)
            ->willReturn($mockPrestashopAddress);

        // Configure address builder to return our mock SDK address
        $this->addressBuilderMock->expects($this->once())
            ->method('build')
            ->with($mockPrestashopAddress)
            ->willReturn($mockSdkAddress);

        // Configure language util to return a language code
        $this->languageUtilMock->expects($this->once())
            ->method('getLanguageCode')
            ->with(2)
            ->willReturn('es');

        // Configure customer util to create a customer object with null server globals
        $this->customerUtilMock->expects($this->once())
            ->method('createCustomer')
            ->with(
                $mockSdkAddress,
                'test@example.com',
                '123456789',
                'John',
                'Doe',
                null,
                null,
                'es',
                'Test Company'
            )
            ->willReturn($mockCustomerDetails);

        // Create a mock for the payment option
        $mockPaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Create a mock order request
        $mockOrderRequest = $this->getMockBuilder(OrderRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        // The order request should have addDelivery called with our customer details
        $mockOrderRequest->expects($this->once())
            ->method('addDelivery')
            ->with($mockCustomerDetails);

        // Make sure server globals are not set
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['HTTP_USER_AGENT']);

        // Execute the method under test
        $this->deliveryBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest);
    }
}
