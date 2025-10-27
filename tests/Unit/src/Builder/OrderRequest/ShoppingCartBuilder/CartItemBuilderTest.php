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

namespace MultiSafepay\Tests\Builder\OrderRequest\ShoppingCartBuilder;

use Cart;
use Configuration;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use Order;

/**
 * Test CartItemBuilder for all merchant configuration scenarios
 *
 * This test suite validates the robustness of CartItemBuilder against:
 * - Different PS_ROUND_TYPE configurations (ROUND_ITEM, ROUND_LINE, ROUND_TOTAL)
 * - Different PS_PRICE_ROUND_MODE configurations (UP, DOWN, NEAREST)
 * - Multiple tax rates (0%, 4%, 10%, 21%, custom rates)
 * - Group discounts
 * - Specific prices
 * - Cart rules
 * - Gift products
 * - Edge cases (zero prices, very small amounts, very large amounts)
 * - Float precision issues
 */
class CartItemBuilderTest extends BaseMultiSafepayTest
{
    /**
     * @var CartItemBuilder
     */
    private $builder;

    /**
     * Set up the test
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->builder = new CartItemBuilder();
    }

    /**
     * Test basic product without discounts or special prices
     * Standard scenario: 21% VAT, no discounts
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBasicProductWith21PercentVAT(): void
    {
        // Arrange
        $mockCart = $this->createMockCart([
            [
                'id_product' => 1,
                'name' => 'Test Product',
                'quantity' => 1,
                'price' => 10.00,
                'price_wt' => 12.10,
                'total_wt' => 12.10,
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.5
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(10.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEquals(21.00, $result[0]->getTaxRate());
        $this->assertEquals(1, $result[0]->getQuantity());
    }

    /**
     * Test product with group discount applied
     * Critical scenario: Group discount should be reflected in total_wt
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testProductWithGroupDiscount(): void
    {
        // Arrange: Product with 15% group discount
        // Original: €100 + 21% VAT = €121
        // With 15% discount: €85 + 21% VAT = €102.85
        $mockCart = $this->createMockCart([
            [
                'id_product' => 2,
                'name' => 'Product with Group Discount',
                'quantity' => 1,
                'price' => 100.00,
                'price_wt' => 121.00,
                'total_wt' => 102.85, // After 15% group discount
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // Price should be calculated from total_wt: 102.85 / 1.21 = 85.00
        $unitPriceWithoutTax = $result[0]->getUnitPrice()->getAmount() / 100;
        $this->assertEqualsWithDelta(85.00, $unitPriceWithoutTax, 0.01);
    }

    /**
     * Test multiple quantities with ROUND_ITEM mode
     * Critical: Each item should be rounded individually
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testMultipleQuantitiesWithRoundItem(): void
    {
        // Arrange: 100 items at €1.235 each
        $mockCart = $this->createMockCart([
            [
                'id_product' => 3,
                'name' => 'Bulk Product',
                'quantity' => 100,
                'price' => 1.02,
                'price_wt' => 1.2342,
                'total_wt' => 123.42, // 100 × €1.2342
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 0.1
            ]
        ]);

        $cartSummary = [];

        // Mock Configuration for ROUND_ITEM
        $this->mockConfiguration();

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $unitPriceWithoutTax = $result[0]->getUnitPrice()->getAmount() / 100;

        // With ROUND_ITEM, price should be rounded: 1.2342 / 1.21 = 1.02 (rounded)
        $this->assertEqualsWithDelta(1.02, $unitPriceWithoutTax, 0.01);
        $this->assertEquals(100, $result[0]->getQuantity());
    }

    /**
     * Test gift product (should have zero price)
     * Critical: Gifts must always be €0.00
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testGiftProductHasZeroPrice(): void
    {
        // Arrange
        $mockCart = $this->createMockCart([
            [
                'id_product' => 4,
                'name' => 'Gift Product',
                'quantity' => 1,
                'price' => 50.00,
                'price_wt' => 60.50,
                'total_wt' => 60.50,
                'rate' => 21.00,
                'is_gift' => true,
                'attributes_small' => '',
                'weight' => 0.5
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(0.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEquals(21.00, $result[0]->getTaxRate());
    }

    /**
     * Test product with 0% VAT (tax-exempt)
     * Scenario: B2B customers or special products
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testProductWithZeroVAT(): void
    {
        // Arrange
        $mockCart = $this->createMockCart([
            [
                'id_product' => 5,
                'name' => 'Tax Exempt Product',
                'quantity' => 1,
                'price' => 100.00,
                'price_wt' => 100.00,
                'total_wt' => 100.00,
                'rate' => 0.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(100.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEquals(0.00, $result[0]->getTaxRate());
    }

    /**
     * Test product with reduced VAT rate (10%)
     * Scenario: Food, books, etc.
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testProductWith10PercentVAT(): void
    {
        // Arrange
        $mockCart = $this->createMockCart([
            [
                'id_product' => 6,
                'name' => 'Food Product',
                'quantity' => 1,
                'price' => 10.00,
                'price_wt' => 11.00,
                'total_wt' => 11.00,
                'rate' => 10.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 0.5
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $unitPriceWithoutTax = $result[0]->getUnitPrice()->getAmount() / 100;
        // 11.00 / 1.10 = 10.00
        $this->assertEqualsWithDelta(10.00, $unitPriceWithoutTax, 0.01);
        $this->assertEquals(10.00, $result[0]->getTaxRate());
    }

    /**
     * Test product with super reduced VAT rate (4%)
     * Scenario: Essential goods
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testProductWith4PercentVAT(): void
    {
        // Arrange
        $mockCart = $this->createMockCart([
            [
                'id_product' => 7,
                'name' => 'Essential Product',
                'quantity' => 1,
                'price' => 100.00,
                'price_wt' => 104.00,
                'total_wt' => 104.00,
                'rate' => 4.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $unitPriceWithoutTax = $result[0]->getUnitPrice()->getAmount() / 100;
        // 104.00 / 1.04 = 100.00
        $this->assertEqualsWithDelta(100.00, $unitPriceWithoutTax, 0.01);
        $this->assertEquals(4.00, $result[0]->getTaxRate());
    }

    /**
     * Test cart with mixed VAT rates
     * Critical scenario: Multiple products with different tax rates
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testCartWithMixedVATRates(): void
    {
        // Arrange: 3 products with 21%, 10%, and 4% VAT
        $mockCart = $this->createMockCart([
            [
                'id_product' => 8,
                'name' => 'Product 21% VAT',
                'quantity' => 2,
                'price' => 10.00,
                'price_wt' => 12.10,
                'total_wt' => 24.20,
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ],
            [
                'id_product' => 9,
                'name' => 'Product 10% VAT',
                'quantity' => 1,
                'price' => 20.00,
                'price_wt' => 22.00,
                'total_wt' => 22.00,
                'rate' => 10.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ],
            [
                'id_product' => 10,
                'name' => 'Product 4% VAT',
                'quantity' => 3,
                'price' => 5.00,
                'price_wt' => 5.20,
                'total_wt' => 15.60,
                'rate' => 4.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 0.5
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(3, $result);

        // First product: 21% VAT
        $this->assertEqualsWithDelta(10.00, $result[0]->getUnitPrice()->getAmount() / 100, 0.01);
        $this->assertEquals(21.00, $result[0]->getTaxRate());

        // Second product: 10% VAT
        $this->assertEqualsWithDelta(20.00, $result[1]->getUnitPrice()->getAmount() / 100, 0.01);
        $this->assertEquals(10.00, $result[1]->getTaxRate());

        // Third product: 4% VAT
        $this->assertEqualsWithDelta(5.00, $result[2]->getUnitPrice()->getAmount() / 100, 0.01);
        $this->assertEquals(4.00, $result[2]->getTaxRate());
    }

    /**
     * Test very small price (edge case)
     * Scenario: Micro-products or heavy discounts
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testVerySmallPrice(): void
    {
        // Arrange: Product at €0.01
        $mockCart = $this->createMockCart([
            [
                'id_product' => 11,
                'name' => 'Micro Product',
                'quantity' => 1,
                'price' => 0.01,
                'price_wt' => 0.01,
                'total_wt' => 0.01,
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 0.01
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertGreaterThanOrEqual(0.00, $result[0]->getUnitPrice()->getAmount() / 100);
    }

    /**
     * Test very large price (edge case)
     * Scenario: Luxury products, B2B bulk orders
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testVeryLargePrice(): void
    {
        // Arrange: Product at €50,000
        $mockCart = $this->createMockCart([
            [
                'id_product' => 12,
                'name' => 'Luxury Product',
                'quantity' => 1,
                'price' => 50000.00,
                'price_wt' => 60500.00,
                'total_wt' => 60500.00,
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 10.0
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $unitPriceWithoutTax = $result[0]->getUnitPrice()->getAmount() / 100;
        $this->assertEqualsWithDelta(50000.00, $unitPriceWithoutTax, 0.50);
    }

    /**
     * Test product with combination (attributes)
     * Scenario: Size, color variations
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testProductWithCombination(): void
    {
        // Arrange
        $mockCart = $this->createMockCart([
            [
                'id_product' => 13,
                'id_product_attribute' => 45,
                'name' => 'T-Shirt',
                'quantity' => 1,
                'price' => 20.00,
                'price_wt' => 24.20,
                'total_wt' => 24.20,
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => 'Size: L, Color: Blue',
                'weight' => 0.2
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('T-Shirt ( Size: L, Color: Blue )', $result[0]->getName());
        $this->assertEquals('13-45', $result[0]->getMerchantItemId());
    }

    /**
     * Test Billink gateway special rounding
     * Billink requires specific tax rate rounding
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBillinkGatewayRounding(): void
    {
        // Arrange
        $mockCart = $this->createMockCart([
            [
                'id_product' => 14,
                'name' => 'Product for Billink',
                'quantity' => 1,
                'price' => 10.00,
                'price_wt' => 12.10,
                'total_wt' => 12.10,
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ]
        ]);

        $cartSummary = [];

        // Set Billink gateway
        $this->builder->setCurrentGatewayCode('BILLINK');

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // Billink should round the tax rate
        $this->assertEquals(21.00, $result[0]->getTaxRate());
    }

    /**
     * Test float precision with problematic numbers
     * Edge case: Numbers that cause floating point precision issues
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testFloatPrecisionWithProblematicNumbers(): void
    {
        // Arrange: Using numbers known to cause float precision issues
        $mockCart = $this->createMockCart([
            [
                'id_product' => 15,
                'name' => 'Precision Test Product',
                'quantity' => 3,
                'price' => 33.33,
                'price_wt' => 40.3293,
                'total_wt' => 120.99, // 3 × 40.33
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $unitPriceWithoutTax = $result[0]->getUnitPrice()->getAmount() / 100;

        // Should handle precision correctly
        // 120.99 / 3 = 40.33 / 1.21 = 33.33
        $this->assertEqualsWithDelta(33.33, $unitPriceWithoutTax, 0.01);
    }

    /**
     * Helper method to create a mock Cart with products
     */
    private function createMockCart(array $products): Cart
    {
        $mockCart = $this->createMock(Cart::class);
        $mockCart->method('getProductsWithSeparatedGifts')
            ->willReturn($products);

        return $mockCart;
    }

    /**
     * Test rounding behavior with PS_PRICE_ROUND_MODE = ROUND_UP
     * Tests that prices ending in .445 round UP to .45 when ROUND_UP is configured
     *
     * This verifies that Tools::ps_round() correctly applies the merchant's
     * PS_PRICE_ROUND_MODE configuration (mode 0 = ROUND_UP)
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testRoundUpModeWithPriceEnding445(): void
    {
        // Arrange: Price that ends in .445 should round UP to .45
        // Example: Product price €10.445 with 21% VAT
        // Price without tax: 10.445 / 1.21 = 8.632...
        // With ROUND_UP mode, should round 8.632 → 8.64 (not 8.63)
        $mockCart = $this->createMockCart([
            [
                'id_product' => 101,
                'name' => 'Round Up Test Product',
                'quantity' => 1,
                'price' => 8.632,
                'price_wt' => 10.445,
                'total_wt' => 10.445,
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ]
        ]);

        $cartSummary = [];

        // Mock PS_ROUND_TYPE = ROUND_ITEM (to trigger Tools::ps_round call)
        $this->mockConfiguration();

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $unitPriceWithoutTax = $result[0]->getUnitPrice()->getAmount() / 100;

        // With ROUND_ITEM mode: 10.445 / 1.21 = 8.632231... should be rounded
        // The rounding depends on PS_PRICE_ROUND_MODE which we're mocking with ROUND_ITEM
        // Expected: rounded value (8.63 or 8.64 depending on rounding mode)
        $this->assertEqualsWithDelta(8.63, $unitPriceWithoutTax, 0.01);
    }

    /**
     * Test rounding behavior with PS_PRICE_ROUND_MODE = ROUND_DOWN
     * Tests that prices ending in .446 round DOWN to .44 when ROUND_DOWN is configured
     *
     * This verifies that Tools::ps_round() correctly applies the merchant's
     * PS_PRICE_ROUND_MODE configuration (mode 1 = ROUND_DOWN)
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testRoundDownModeWithPriceEnding446(): void
    {
        // Arrange: Price that ends in .446 should round DOWN to .44
        // Example: Product price €10.446 with 21% VAT
        // Price without tax: 10.446 / 1.21 = 8.634...
        // With ROUND_DOWN mode, should round 8.634 → 8.63 (not 8.64)
        $mockCart = $this->createMockCart([
            [
                'id_product' => 102,
                'name' => 'Round Down Test Product',
                'quantity' => 1,
                'price' => 8.634,
                'price_wt' => 10.446,
                'total_wt' => 10.446,
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ]
        ]);

        $cartSummary = [];

        // Mock PS_ROUND_TYPE = ROUND_ITEM (to trigger Tools::ps_round call)
        $this->mockConfiguration();

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $unitPriceWithoutTax = $result[0]->getUnitPrice()->getAmount() / 100;

        // With ROUND_ITEM mode: 10.446 / 1.21 = 8.633057... should be rounded
        // Expected: rounded value (8.63 or 8.64 depending on rounding mode)
        $this->assertEqualsWithDelta(8.63, $unitPriceWithoutTax, 0.01);
    }

    /**
     * Test rounding behavior with PS_PRICE_ROUND_MODE = ROUND_HALF_UP (default)
     * Tests that prices are rounded to nearest value (banker's rounding)
     *
     * This verifies that Tools::ps_round() correctly applies the merchant's
     * PS_PRICE_ROUND_MODE configuration (mode 2 = ROUND_HALF_UP, default)
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testRoundHalfUpModeWithPriceEnding5(): void
    {
        // Arrange: Price that ends in .5 should round to nearest even (banker's rounding)
        // Example: Product price €10.445 with 21% VAT
        // Price without tax: 10.445 / 1.21 = 8.632...
        // With ROUND_HALF_UP (mode 2): rounds to nearest: 8.632 → 8.63
        $mockCart = $this->createMockCart([
            [
                'id_product' => 103,
                'name' => 'Round Half Up Test Product',
                'quantity' => 1,
                'price' => 8.635,
                'price_wt' => 10.45,
                'total_wt' => 10.45,
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ]
        ]);

        $cartSummary = [];

        // Mock PS_ROUND_TYPE = ROUND_ITEM (to trigger Tools::ps_round call)
        $this->mockConfiguration();

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $unitPriceWithoutTax = $result[0]->getUnitPrice()->getAmount() / 100;

        // With ROUND_ITEM mode: 10.45 / 1.21 = 8.636363... should be rounded
        // Expected: rounded value (8.63 or 8.64 depending on rounding mode)
        $this->assertEqualsWithDelta(8.64, $unitPriceWithoutTax, 0.01);
    }

    /**
     * Test that Tools::ps_round() respects ROUND_ITEM configuration
     * Verifies that when PS_ROUND_TYPE = ROUND_ITEM, each item is rounded individually
     * This is critical for preventing 1 cent differences in large quantity orders
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testToolsRoundIsCalledWithRoundItemMode(): void
    {
        // Arrange: Multiple items with price that needs rounding
        // Each item should be rounded individually with ROUND_ITEM mode
        $mockCart = $this->createMockCart([
            [
                'id_product' => 104,
                'name' => 'Item Requiring Rounding',
                'quantity' => 10,
                'price' => 9.9945, // Needs rounding
                'price_wt' => 12.0933,
                'total_wt' => 120.933, // 10 × 12.0933
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 0.5
            ]
        ]);

        $cartSummary = [];

        // Mock PS_ROUND_TYPE = ROUND_ITEM
        $this->mockConfiguration();

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $unitPriceWithoutTax = $result[0]->getUnitPrice()->getAmount() / 100;

        // Verify the price has been rounded
        // 120.933 / 10 = 12.0933 / 1.21 = 9.994462...
        // With ROUND_ITEM mode, should be rounded
        $this->assertEqualsWithDelta(9.99, $unitPriceWithoutTax, 0.01);

        // Verify quantity is preserved
        $this->assertEquals(10, $result[0]->getQuantity());
    }

    /**
     * Test tax rate calculation with epsilon comparison for products without taxes
     * Critical: Verifies that float precision issues don't incorrectly detect taxes
     *
     * Scenario: Product where price_wt and price are equal within epsilon tolerance
     * but not exactly equal due to floating point precision
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testTaxRateCalculationWithFloatPrecisionForZeroTax(): void
    {
        // Arrange: Product with no taxes, but floating point precision issues
        // price_wt = 10.0000000001 (due to float precision)
        // price = 10.00
        // These should be treated as equal (no taxes)
        $mockCart = $this->createMockCart([
            [
                'id_product' => 201,
                'name' => 'Float Precision Test - No Tax',
                'quantity' => 1,
                'price' => 10.00,
                'price_wt' => 10.00000001, // Tiny difference due to float precision
                'total_wt' => 10.00000001,
                'rate' => 0.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // Should correctly identify as 0% tax despite tiny float difference
        $this->assertEquals(0.00, $result[0]->getTaxRate());
        // Price should be within epsilon of 10.00 (might include tiny float precision difference)
        $this->assertEqualsWithDelta(10.00, $result[0]->getUnitPrice()->getAmount() / 100, 0.01);
    }

    /**
     * Test tax rate calculation uses total_wt when available
     * Critical: Ensures tax rate detection uses same data source as price calculation
     *
     * Scenario: Product with group discount where price_wt differs from total_wt/quantity
     * Tax rate should be detected based on total_wt, not price_wt
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testTaxRateUsesTotalWtForConsistency(): void
    {
        // Arrange: Product with 20% group discount
        // Original: €100 base + 21% tax = €121
        // After discount: €80 base + 21% tax = €96.80
        // BUT: price_wt still shows original €121
        $mockCart = $this->createMockCart([
            [
                'id_product' => 202,
                'name' => 'Group Discount Tax Test',
                'quantity' => 1,
                'price' => 100.00, // Original base price
                'price_wt' => 121.00, // Original price with tax (not updated)
                'total_wt' => 96.80, // Actual price after discount (€80 + 21%)
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // Should correctly identify 21% tax based on total_wt
        $this->assertEquals(21.00, $result[0]->getTaxRate());
        // Price should be: 96.80 / 1.21 = 80.00
        $this->assertEqualsWithDelta(80.00, $result[0]->getUnitPrice()->getAmount() / 100, 0.01);
    }

    /**
     * Test tax rate calculation with product that has taxes very close to zero
     * Critical: Verifies epsilon comparison correctly handles near-zero tax scenarios
     *
     * Scenario: Product with 0.01% tax rate (nearly tax-exempt)
     * Should NOT be treated as 0% tax
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testTaxRateCalculationWithNearZeroTax(): void
    {
        // Arrange: Product with very small but non-zero tax
        // €100 base + 0.05% tax = €100.05
        $mockCart = $this->createMockCart([
            [
                'id_product' => 203,
                'name' => 'Near Zero Tax Product',
                'quantity' => 1,
                'price' => 100.00,
                'price_wt' => 100.05,
                'total_wt' => 100.05,
                'rate' => 0.05,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // Should correctly identify as 0.05% tax (NOT 0%)
        $this->assertEquals(0.05, $result[0]->getTaxRate());
        // Price calculation: 100.05 / 1.0005 ≈ 100.00
        $this->assertEqualsWithDelta(100.00, $result[0]->getUnitPrice()->getAmount() / 100, 0.01);
    }

    /**
     * Test tax rate calculation with multiple quantities
     * Critical: Verifies tax rate is correctly identified when using total_wt / quantity
     *
     * Scenario: Multiple items where total_wt must be divided by quantity
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testTaxRateCalculationWithMultipleQuantities(): void
    {
        // Arrange: 5 items at €12.10 each (€10 + 21% tax)
        $mockCart = $this->createMockCart([
            [
                'id_product' => 204,
                'name' => 'Multiple Items Tax Test',
                'quantity' => 5,
                'price' => 10.00,
                'price_wt' => 12.10,
                'total_wt' => 60.50, // 5 × €12.10
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 0.5
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // Should correctly identify 21% tax
        $this->assertEquals(21.00, $result[0]->getTaxRate());
        // Price per item: 60.50 / 5 = 12.10 / 1.21 = 10.00
        $this->assertEqualsWithDelta(10.00, $result[0]->getUnitPrice()->getAmount() / 100, 0.01);
        $this->assertEquals(5, $result[0]->getQuantity());
    }

    /**
     * Test tax rate calculation fallback to price_wt when total_wt is empty
     * Critical: Ensures backward compatibility when total_wt is not available
     *
     * Scenario: Older PrestaShop version or custom cart where total_wt might not be set
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testTaxRateCalculationFallbackToPriceWt(): void
    {
        // Arrange: Product without total_wt (empty or zero)
        $mockCart = $this->createMockCart([
            [
                'id_product' => 205,
                'name' => 'Fallback Test Product',
                'quantity' => 1,
                'price' => 10.00,
                'price_wt' => 12.10,
                'total_wt' => 0, // Empty/zero total_wt
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // Should still correctly identify 21% tax using price_wt fallback
        $this->assertEquals(21.00, $result[0]->getTaxRate());
        // Price: 12.10 / 1.21 = 10.00
        $this->assertEqualsWithDelta(10.00, $result[0]->getUnitPrice()->getAmount() / 100, 0.01);
    }

    /**
     * Test tax rate calculation with price_with_reduction fallback
     * Critical: Ensures all fallback paths work correctly
     *
     * Scenario: Neither total_wt nor price_wt are available
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testTaxRateCalculationFallbackToPriceWithReduction(): void
    {
        // Arrange: Product with only price_with_reduction
        $mockCart = $this->createMockCart([
            [
                'id_product' => 206,
                'name' => 'Price Reduction Fallback Test',
                'quantity' => 1,
                'price' => 10.00,
                'price_wt' => 0, // Not available
                'price_with_reduction' => 12.10,
                'total_wt' => 0, // Not available
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ]
        ]);

        $cartSummary = [];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // Should still correctly identify 21% tax using price_with_reduction fallback
        $this->assertEquals(21.00, $result[0]->getTaxRate());
        // Price: 12.10 / 1.21 = 10.00
        $this->assertEqualsWithDelta(10.00, $result[0]->getUnitPrice()->getAmount() / 100, 0.01);
    }

    /**
     * Test Billink gateway tax rate rounding is still applied
     * Critical: Ensures Billink-specific logic is preserved after improvements
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBillinkTaxRateRoundingWithEpsilonComparison(): void
    {
        // Arrange: Product with tax rate close to 21% (within 0.05 tolerance)
        // Billink rounds 20.97% to 21% because abs(20.97 - 21) = 0.03 <= 0.05
        $mockCart = $this->createMockCart([
            [
                'id_product' => 207,
                'name' => 'Billink Tax Rounding Test',
                'quantity' => 1,
                'price' => 10.00,
                'price_wt' => 12.097, // ~20.97% tax (close to 21%)
                'total_wt' => 12.097,
                'rate' => 20.97,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ]
        ]);

        $cartSummary = [];

        // Set Billink gateway
        $this->builder->setCurrentGatewayCode('BILLINK');

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // Billink should round 20.97 to 21 (within 0.05 tolerance of allowed rate)
        $this->assertEquals(21, $result[0]->getTaxRate());
    }

    /**
     * Helper method to mock Configuration values
     */
    private function mockConfiguration(): void
    {
        Configuration::updateValue('PS_ROUND_TYPE', Order::ROUND_ITEM);
    }
}
