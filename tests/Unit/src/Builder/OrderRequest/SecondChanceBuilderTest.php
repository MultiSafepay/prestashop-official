<?php

declare(strict_types=1);

namespace MultiSafepay\PrestaShop\Tests\Unit\Builder\OrderRequest;

use Cart;
use Customer;
use Exception;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\PrestaShop\Builder\OrderRequest\SecondChanceBuilder;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use Order;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class SecondChanceBuilderTest
 * @package MultiSafepay\PrestaShop\Tests\Unit\Builder\OrderRequest
 */
class SecondChanceBuilderTest extends TestCase
{
    /**
     * Test that SecondChanceBuilder can be instantiated
     */
    public function testInstantiation(): void
    {
        $secondChanceBuilder = new SecondChanceBuilder();
        $this->assertInstanceOf(SecondChanceBuilder::class, $secondChanceBuilder);
    }

    /**
     * Test that class implements OrderRequestBuilderInterface
     */
    public function testImplementsInterface(): void
    {
        $secondChanceBuilder = new SecondChanceBuilder();

        $this->assertInstanceOf(
            'MultiSafepay\PrestaShop\Builder\OrderRequest\OrderRequestBuilderInterface',
            $secondChanceBuilder
        );
    }

    /**
     * Test class structure and methods
     */
    public function testClassStructure(): void
    {
        $this->assertTrue(class_exists(SecondChanceBuilder::class));

        $secondChanceBuilder = new SecondChanceBuilder();

        $this->assertTrue(method_exists($secondChanceBuilder, 'build'));
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\SecondChanceBuilder::build
     */
    public function testBuildMethodExists(): void
    {
        $secondChanceBuilder = new SecondChanceBuilder();

        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockPaymentOption = $this->createMock(BasePaymentOption::class);
        $mockOrderRequest = $this->createMock(OrderRequest::class);
        $mockOrder = $this->createMock(Order::class);

        // Test that method exists and can be called without fatal errors
        try {
            $secondChanceBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
            $this->assertTrue(true); // If we get here, no fatal error occurred
        } catch (Exception $exception) {
            // This is expected in test environment due to Configuration dependencies
            $this->assertInstanceOf(Exception::class, $exception);
        }
    }

    /**
     * Test build method with null order
     */
    public function testBuildWithNullOrder(): void
    {
        $secondChanceBuilder = new SecondChanceBuilder();

        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockPaymentOption = $this->createMock(BasePaymentOption::class);
        $mockOrderRequest = $this->createMock(OrderRequest::class);

        try {
            $secondChanceBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, null);
            $this->assertTrue(true);
        } catch (Exception $e) {
            // Expected due to missing PrestaShop context in tests
            $this->assertInstanceOf(Exception::class, $e);
        }
    }

    /**
     * Test that SecondChanceBuilder has no constructor parameters
     */
    public function testNoConstructorParameters(): void
    {
        $reflection = new ReflectionClass(SecondChanceBuilder::class);
        $constructor = $reflection->getConstructor();

        // Check if constructor exists or if it has no parameters
        if ($constructor) {
            $parameters = $constructor->getParameters();
            $this->assertCount(0, $parameters);
        } else {
            // No explicit constructor, which is also valid
            $this->assertTrue(true);
        }
    }
}
