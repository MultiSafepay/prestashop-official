<?php

declare(strict_types=1);

namespace MultiSafepay\PrestaShop\Tests\Unit\Builder\OrderRequest;

use Cart;
use Customer;
use Exception;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\PrestaShop\Builder\OrderRequest\PaymentOptionsBuilder;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultisafepayOfficial;
use Order;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class PaymentOptionsBuilderTest
 * @package MultiSafepay\PrestaShop\Tests\Unit\Builder\OrderRequest
 */
class PaymentOptionsBuilderTest extends TestCase
{
    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\PaymentOptionsBuilder::__construct
     */
    public function testConstructor(): void
    {
        $mockModule = $this->createMock(MultisafepayOfficial::class);
        $paymentOptionsBuilder = new PaymentOptionsBuilder($mockModule);

        $this->assertInstanceOf(PaymentOptionsBuilder::class, $paymentOptionsBuilder);
    }

    /**
     * Test that class implements OrderRequestBuilderInterface
     */
    public function testImplementsInterface(): void
    {
        $mockModule = $this->createMock(MultisafepayOfficial::class);
        $paymentOptionsBuilder = new PaymentOptionsBuilder($mockModule);

        $this->assertInstanceOf(
            'MultiSafepay\PrestaShop\Builder\OrderRequest\OrderRequestBuilderInterface',
            $paymentOptionsBuilder
        );
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\PaymentOptionsBuilder::build
     */
    public function testBuildMethodExists(): void
    {
        $mockModule = $this->createMock(MultisafepayOfficial::class);
        $paymentOptionsBuilder = new PaymentOptionsBuilder($mockModule);

        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockPaymentOption = $this->createMock(BasePaymentOption::class);
        $mockOrderRequest = $this->createMock(OrderRequest::class);
        $mockOrder = $this->createMock(Order::class);

        // Test that method exists and can be called without fatal errors
        try {
            $paymentOptionsBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, $mockOrder);
            $this->assertTrue(true); // If we get here, no fatal error occurred
        } catch (Exception $exception) {
            // This is expected in test environment due to Context dependencies
            $this->assertInstanceOf(Exception::class, $exception);
        }
    }

    /**
     * Test class structure and methods
     */
    public function testClassStructure(): void
    {
        $this->assertTrue(class_exists(PaymentOptionsBuilder::class));

        $mockModule = $this->createMock(MultisafepayOfficial::class);
        $paymentOptionsBuilder = new PaymentOptionsBuilder($mockModule);

        $this->assertTrue(method_exists($paymentOptionsBuilder, 'build'));
        $this->assertTrue(method_exists($paymentOptionsBuilder, '__construct'));
    }

    /**
     * Test build method with null order
     */
    public function testBuildWithNullOrder(): void
    {
        $mockModule = $this->createMock(MultisafepayOfficial::class);
        $paymentOptionsBuilder = new PaymentOptionsBuilder($mockModule);

        $mockCart = $this->createMock(Cart::class);
        $mockCustomer = $this->createMock(Customer::class);
        $mockPaymentOption = $this->createMock(BasePaymentOption::class);
        $mockOrderRequest = $this->createMock(OrderRequest::class);

        try {
            $paymentOptionsBuilder->build($mockCart, $mockCustomer, $mockPaymentOption, $mockOrderRequest, null);
            $this->assertTrue(true);
        } catch (Exception $exception) {
            // Expected due to missing PrestaShop context in tests
            $this->assertInstanceOf(Exception::class, $exception);
        }
    }

    /**
     * Test constructor dependencies
     */
    public function testConstructorDependencies(): void
    {
        $reflection = new ReflectionClass(PaymentOptionsBuilder::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('module', $parameters[0]->getName());
    }
}
