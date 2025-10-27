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
use MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder;
use MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultisafepayOfficial;
use Order;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Integration tests for all ShoppingCartBuilders working together
 * Critical: Validates that all builders produce consistent results when combined
 *
 * @package MultiSafepay\Tests\Builder\OrderRequest\ShoppingCartBuilder
 */
class ShoppingCartBuilderIntegrationTest extends BaseMultiSafepayTest
{
    /**
     * @var CartItemBuilder
     */
    private $cartItemBuilder;

    /**
     * @var DiscountItemBuilder
     */
    private $discountItemBuilder;

    /**
     * @var ShippingItemBuilder
     */
    private $shippingItemBuilder;

    /**
     * @var WrappingItemBuilder
     */
    private $wrappingItemBuilder;

    /**
     * Set up the test
     */
    public function setUp(): void
    {
        parent::setUp();

        $mockModule = $this->createMock(MultisafepayOfficial::class);
        $mockModule->method('l')->willReturnCallback(function ($string) {
            return $string;
        });

        $this->cartItemBuilder = new CartItemBuilder();
        $this->discountItemBuilder = new DiscountItemBuilder($mockModule);
        $this->shippingItemBuilder = new ShippingItemBuilder($mockModule);
        $this->wrappingItemBuilder = new WrappingItemBuilder($mockModule);
    }

    /**
     * Test complete shopping cart with mixed products, discount, shipping and wrapping
     * Scenario: Real-world cart with multiple products, different VAT rates, discount, shipping and gift wrap
     * Critical: All builders must work together to produce accurate total
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testCompleteShoppingCartIntegration(): void
    {
        // Arrange - Create a realistic cart with multiple products and mixed VAT
        $mockCart = $this->createMockCart([
            // Product 1: Book with 4% VAT (reduced rate)
            [
                'id_product' => 1,
                'id_product_attribute' => 0,
                'name' => 'PHP Programming Book',
                'quantity' => 2,
                'price' => 24.04, // €25 with 4% VAT
                'price_wt' => 25.00,
                'total_wt' => 50.00, // 2 × €25
                'rate' => 4.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 0.5
            ],
            // Product 2: T-Shirt with 21% VAT (standard rate)
            [
                'id_product' => 2,
                'id_product_attribute' => 0,
                'name' => 'Developer T-Shirt',
                'quantity' => 3,
                'price' => 16.53,
                'price_wt' => 20.00,
                'total_wt' => 60.00, // 3 × €20
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => 'Size: L',
                'weight' => 0.2
            ],
            // Product 3: Food with 10% VAT
            [
                'id_product' => 3,
                'id_product_attribute' => 0,
                'name' => 'Organic Coffee',
                'quantity' => 1,
                'price' => 9.09,
                'price_wt' => 10.00,
                'total_wt' => 10.00,
                'rate' => 10.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 0.3
            ],
        ]);

        // Cart summary with discount, shipping and wrapping
        $cartSummary = [
            'total_discounts_tax_exc' => 10.00,
            'total_discounts' => 11.40, // ~11.40 with mixed VAT
            'total_shipping_tax_exc' => 5.00,
            'total_shipping' => 6.05, // €5 + 21% VAT
            'total_wrapping' => 2.50, // Gift wrapping (no tax in this test)
            'total_wrapping_tax_exc' => 2.50, // Same price = no tax applied
        ];

        // Mock Configuration
        $this->mockConfiguration();

        // Act - Build all cart items
        $productItems = $this->cartItemBuilder->build($mockCart, $cartSummary, 'EUR');
        $discountItems = $this->discountItemBuilder->build($mockCart, $cartSummary, 'EUR');
        $shippingItems = $this->shippingItemBuilder->build($mockCart, $cartSummary, 'EUR');
        $wrappingItems = $this->wrappingItemBuilder->build($mockCart, $cartSummary, 'EUR');

        // Combine all items
        $allItems = array_merge($productItems, $discountItems, $shippingItems, $wrappingItems);

        // Assert - Verify item counts
        $this->assertCount(3, $productItems, 'Should have 3 product items');
        $this->assertCount(1, $discountItems, 'Should have 1 discount item');
        $this->assertCount(1, $shippingItems, 'Should have 1 shipping item');
        $this->assertCount(1, $wrappingItems, 'Should have 1 wrapping item');
        $this->assertCount(6, $allItems, 'Total items should be 6 (3 products + 1 discount + 1 shipping + 1 wrapping)');

        // Assert - Verify product items (prices WITHOUT VAT - CartItemBuilder calculates using total_wt / (1 + rate/100))
        $this->assertEquals('PHP Programming Book', $productItems[0]->getName());
        $this->assertEquals(2, $productItems[0]->getQuantity());
        // €25.00 / 1.04 = €24.038461... (not rounded because ROUND_TYPE is not ROUND_ITEM)
        $this->assertEqualsWithDelta(2404, $productItems[0]->getUnitPrice()->getAmount(), 1); // Within 1 cent
        $this->assertEquals('4.00', $productItems[0]->getTaxRate());

        $this->assertEquals('Developer T-Shirt ( Size: L )', $productItems[1]->getName());
        $this->assertEquals(3, $productItems[1]->getQuantity());
        // €20.00 / 1.21 = €16.528925... (not rounded because ROUND_TYPE is not ROUND_ITEM)
        $this->assertEqualsWithDelta(1653, $productItems[1]->getUnitPrice()->getAmount(), 1); // Within 1 cent
        $this->assertEquals('21.00', $productItems[1]->getTaxRate());

        $this->assertEquals('Organic Coffee', $productItems[2]->getName());
        $this->assertEquals(1, $productItems[2]->getQuantity());
        // €10.00 / 1.10 = €9.090909... (not rounded because ROUND_TYPE is not ROUND_ITEM)
        $this->assertEqualsWithDelta(909, $productItems[2]->getUnitPrice()->getAmount(), 1); // Within 1 cent
        $this->assertEquals('10.00', $productItems[2]->getTaxRate());

        // Assert - Verify discount (negative amount, WITHOUT VAT - uses total_discounts_tax_exc)
        $this->assertEquals('Discount', $discountItems[0]->getName());
        $this->assertEquals(1, $discountItems[0]->getQuantity());
        $this->assertTrue($discountItems[0]->getUnitPrice()->getAmount() < 0, 'Discount should be negative');
        $this->assertEqualsWithDelta(-1000, $discountItems[0]->getUnitPrice()->getAmount(), 1); // €10.00 without VAT

        // Assert - Verify shipping (WITHOUT VAT - uses total_shipping_tax_exc)
        $this->assertEquals('Shipping', $shippingItems[0]->getName());
        $this->assertEquals(1, $shippingItems[0]->getQuantity());
        $this->assertEquals(500, $shippingItems[0]->getUnitPrice()->getAmount()); // €5.00 without VAT
        $this->assertEqualsWithDelta(21.00, $shippingItems[0]->getTaxRate(), 0.1); // 21% VAT calculated from tax_exc and tax_inc

        // Assert - Verify wrapping (WITHOUT VAT - uses total_wrapping_tax_exc)
        $this->assertEquals('Wrapping', $wrappingItems[0]->getName());
        $this->assertEquals(1, $wrappingItems[0]->getQuantity());
        $this->assertEquals(250, $wrappingItems[0]->getUnitPrice()->getAmount()); // €2.50 in cents without VAT
        $this->assertEquals(0.0, $wrappingItems[0]->getTaxRate(), 'No tax when tax_exc equals tax_inc');

        // Assert - Calculate total amount (critical for payment gateway)
        // All builders use prices WITHOUT VAT, so we sum all items without VAT
        $totalAmount = 0;
        foreach ($allItems as $item) {
            $totalAmount += $item->getUnitPrice()->getAmount() * $item->getQuantity();
        }

        // Expected total WITHOUT VAT:
        // Products: (2 × €24.04) + (3 × €16.53) + (1 × €9.09) = €48.08 + €49.59 + €9.09 = €106.76
        // Discount: -€10.00 (without VAT)
        // Shipping: €5.00 (without VAT)
        // Wrapping: €2.50
        // Total: €106.76 - €10.00 + €5.00 + €2.50 = €104.26
        $expectedTotal = 10426; // in cents
        $this->assertEqualsWithDelta(
            $expectedTotal,
            $totalAmount,
            10,
            'Total amount should match expected cart total within ±10 cents for float precision'
        );
    }

    /**
     * Test cart with high-value products and percentage discount
     * Scenario: Luxury items with large discount
     * Critical: Precision must be maintained even with large numbers
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\CartItemBuilder::build
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testHighValueCartWithPercentageDiscount(): void
    {
        // Arrange - Luxury products
        $mockCart = $this->createMockCart([
            [
                'id_product' => 100,
                'id_product_attribute' => 0,
                'name' => 'Premium Laptop',
                'quantity' => 1,
                'price' => 1652.89,
                'price_wt' => 2000.00,
                'total_wt' => 2000.00,
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 2.0
            ],
        ]);

        // 15% discount on €2000 = €300 discount
        $cartSummary = [
            'total_discounts_tax_exc' => 247.93, // €300 / 1.21
            'total_discounts' => 300.00,
            'total_shipping_tax_exc' => 0,
            'total_shipping' => 0,
        ];

        $this->mockConfiguration();

        // Act
        $productItems = $this->cartItemBuilder->build($mockCart, $cartSummary, 'EUR');
        $discountItems = $this->discountItemBuilder->build($mockCart, $cartSummary, 'EUR');

        // Assert - Verify precision
        // CartItemBuilder calculates price WITHOUT tax: €2000 / 1.21 = €1652.89
        $this->assertEqualsWithDelta(165289, $productItems[0]->getUnitPrice()->getAmount(), 1); // €1652.89 in cents
        $this->assertEqualsWithDelta(-24793, $discountItems[0]->getUnitPrice()->getAmount(), 1); // -€247.93 in cents

        // Net total should be €1652.89 - €247.93 = €1404.96
        $netTotal = $productItems[0]->getUnitPrice()->getAmount() + $discountItems[0]->getUnitPrice()->getAmount();
        $this->assertEqualsWithDelta(140496, $netTotal, 10);
    }

    /**
     * Test cart with free shipping threshold
     * Scenario: Cart over €50 gets free shipping
     * Critical: Shipping item is created with €0.00 cost
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testCartWithFreeShipping(): void
    {
        // Arrange
        $mockCart = $this->createMockCart([
            [
                'id_product' => 1,
                'id_product_attribute' => 0,
                'name' => 'Product',
                'quantity' => 1,
                'price' => 50.00,
                'price_wt' => 60.50,
                'total_wt' => 60.50,
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 1.0
            ],
        ]);

        $cartSummary = [
            'total_shipping_tax_exc' => 0,
            'total_shipping' => 0, // Free shipping
        ];

        // Act
        $shippingItems = $this->shippingItemBuilder->build($mockCart, $cartSummary, 'EUR');

        // Assert - Shipping item is created even with €0 cost
        $this->assertCount(1, $shippingItems, 'Shipping item should be created even with €0 cost');
        $this->assertEquals(0, $shippingItems[0]->getUnitPrice()->getAmount());
        $this->assertEquals(0.0, $shippingItems[0]->getTaxRate());
    }

    /**
     * Test cart without gift wrapping
     * Scenario: Customer didn't select gift wrapping
     * Critical: No wrapping item should be created
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     */
    public function testCartWithoutGiftWrapping(): void
    {
        // Arrange
        $mockCart = $this->createMockCart([
            [
                'id_product' => 1,
                'name' => 'Product',
                'quantity' => 1,
                'price' => 10.00,
                'price_wt' => 12.10,
                'total_wt' => 12.10,
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 0.5
            ],
        ]);

        $cartSummary = [
            'total_wrapping' => 0, // No wrapping
            'total_wrapping_tax_exc' => 0,
        ];

        // Act
        $wrappingItems = $this->wrappingItemBuilder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(0, $wrappingItems, 'Should not create wrapping item when total_wrapping is 0');
    }

    /**
     * Test cart with gift wrapping that has tax applied
     * Scenario: Shop has PS_GIFT_WRAPPING_TAX_RULES_GROUP configured with 21% VAT
     * Critical: Wrapping tax should be calculated correctly from PrestaShop values
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testCartWithTaxedGiftWrapping(): void
    {
        // Arrange
        $mockCart = $this->createMockCart([
            [
                'id_product' => 1,
                'name' => 'Gift Product',
                'quantity' => 1,
                'price' => 10.00,
                'price_wt' => 12.10,
                'total_wt' => 12.10,
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 0.5
            ],
        ]);

        $cartSummary = [
            'total_wrapping' => 3.025, // €2.50 + 21% VAT = €3.025
            'total_wrapping_tax_exc' => 2.50, // €2.50 without tax
        ];

        // Act
        $wrappingItems = $this->wrappingItemBuilder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $wrappingItems, 'Should create wrapping item with tax');
        $this->assertEquals(250, $wrappingItems[0]->getUnitPrice()->getAmount()); // €2.50 without tax
        $this->assertEqualsWithDelta(21.0, $wrappingItems[0]->getTaxRate(), 0.1, 'Should calculate 21% tax rate');
    }

    /**
     * Test cart with 100% discount (promotional campaign)
     * Scenario: Full discount promotion
     * Critical: Final total should be €0, but structure should be valid
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testCartWithFullDiscount(): void
    {
        // Arrange
        $mockCart = $this->createMockCart([
            [
                'id_product' => 1,
                'id_product_attribute' => 0,
                'name' => 'Promotional Product',
                'quantity' => 1,
                'price' => 10.00,
                'price_wt' => 12.10,
                'total_wt' => 12.10,
                'rate' => 21.00,
                'is_gift' => false,
                'attributes_small' => '',
                'weight' => 0.1
            ],
        ]);

        $cartSummary = [
            'total_discounts_tax_exc' => 10.00,
            'total_discounts' => 12.10, // 100% discount
            'total_shipping_tax_exc' => 0,
            'total_shipping' => 0,
        ];

        $this->mockConfiguration();

        // Act
        $productItems = $this->cartItemBuilder->build($mockCart, $cartSummary, 'EUR');
        $discountItems = $this->discountItemBuilder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        // CartItemBuilder calculates price WITHOUT tax: €12.10 / 1.21 = €10.00
        $this->assertEquals(1000, $productItems[0]->getUnitPrice()->getAmount()); // €10.00 in cents
        $this->assertEqualsWithDelta(-1000, $discountItems[0]->getUnitPrice()->getAmount(), 1); // -€10.00 in cents

        // Net should be ~€0
        $netTotal = $productItems[0]->getUnitPrice()->getAmount() + $discountItems[0]->getUnitPrice()->getAmount();
        $this->assertEqualsWithDelta(0, $netTotal, 5);
    }

    /**
     * Helper method to create mock Cart with products
     *
     * @param array $products
     * @return MockObject|Cart
     */
    private function createMockCart(array $products): MockObject
    {
        $mockCart = $this->createMock(Cart::class);
        $mockCart->method('getProductsWithSeparatedGifts')->willReturn($products);
        $mockCart->method('getProducts')->willReturn($products);
        return $mockCart;
    }

    /**
     * Helper method to mock Configuration values
     */
    private function mockConfiguration(): void
    {
        Configuration::updateValue('PS_ROUND_TYPE', Order::ROUND_ITEM);
    }
}
