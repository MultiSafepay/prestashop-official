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
use Carrier;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultisafepayOfficial;

/**
 * Test ShippingItemBuilder for all shipping scenarios
 *
 * This test suite validates shipping calculation robustness against:
 * - Different carriers and shipping methods
 * - Different tax rates on shipping (21%, 10%, 4%, 0%)
 * - Free shipping scenarios
 * - Very small shipping costs (precision edge cases)
 * - Very large shipping costs (B2B, international)
 * - Float precision issues
 */
class ShippingItemBuilderTest extends BaseMultiSafepayTest
{
    /**
     * @var ShippingItemBuilder
     */
    private $builder;

    /**
     * Set up the test
     */
    public function setUp(): void
    {
        parent::setUp();

        $mockModule = $this->createMock(MultisafepayOfficial::class);
        $mockModule->method('l')
            ->willReturn('Shipping');

        $this->builder = new ShippingItemBuilder($mockModule);
    }

    /**
     * Test free shipping (no cost)
     * Should return shipping item even with €0.00 cost
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testFreeShipping(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $mockCarrier = $this->createMock(Carrier::class);
        $mockCarrier->name = 'Free Shipping';

        $cartSummary = [
            'total_shipping_tax_exc' => 0.0,
            'total_shipping' => 0.0,
            'carrier' => $mockCarrier
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(0.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEquals(0.00, $result[0]->getTaxRate());
    }

    /**
     * Test shipping with 21% VAT
     * Standard scenario: Normal shipping cost with standard VAT
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testShippingWith21PercentVAT(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $mockCarrier = $this->createMock(Carrier::class);
        $mockCarrier->name = 'Standard Delivery';

        $cartSummary = [
            'total_shipping_tax_exc' => 10.00,
            'total_shipping' => 12.10, // 10.00 + 21% = 12.10
            'carrier' => $mockCarrier
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(10.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEqualsWithDelta(21.00, $result[0]->getTaxRate(), 0.01);
        $this->assertEquals('Standard Delivery', $result[0]->getName());
    }

    /**
     * Test shipping with 10% VAT (reduced rate)
     * Scenario: Some countries/merchants use reduced VAT for shipping
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testShippingWith10PercentVAT(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $mockCarrier = $this->createMock(Carrier::class);
        $mockCarrier->name = 'Economy Shipping';

        $cartSummary = [
            'total_shipping_tax_exc' => 5.00,
            'total_shipping' => 5.50, // 5.00 + 10% = 5.50
            'carrier' => $mockCarrier
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(5.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEqualsWithDelta(10.00, $result[0]->getTaxRate(), 0.01);
    }

    /**
     * Test shipping with 4% VAT (super reduced rate)
     * Scenario: Special cases for essential goods shipping
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testShippingWith4PercentVAT(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $mockCarrier = $this->createMock(Carrier::class);
        $mockCarrier->name = 'Essential Goods Shipping';

        $cartSummary = [
            'total_shipping_tax_exc' => 10.00,
            'total_shipping' => 10.40, // 10.00 + 4% = 10.40
            'carrier' => $mockCarrier
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(10.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEqualsWithDelta(4.00, $result[0]->getTaxRate(), 0.01);
    }

    /**
     * Test shipping with 0% VAT (tax-exempt)
     * Scenario: B2B, intra-community, or outside EU shipping
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testShippingWith0PercentVAT(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $mockCarrier = $this->createMock(Carrier::class);
        $mockCarrier->name = 'International Shipping';

        $cartSummary = [
            'total_shipping_tax_exc' => 25.00,
            'total_shipping' => 25.00, // No tax
            'carrier' => $mockCarrier
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(25.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEquals(0.00, $result[0]->getTaxRate());
    }

    /**
     * Test very small shipping cost (edge case for epsilon)
     * Should handle tiny shipping costs correctly
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testVerySmallShippingCost(): void
    {
        // Arrange: €0.01 shipping
        $mockCart = $this->createMock(Cart::class);
        $mockCarrier = $this->createMock(Carrier::class);
        $mockCarrier->name = 'Minimal Shipping';

        $cartSummary = [
            'total_shipping_tax_exc' => 0.01,
            'total_shipping' => 0.01,
            'carrier' => $mockCarrier
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(0.01, $result[0]->getUnitPrice()->getAmount() / 100);
    }

    /**
     * Test shipping cost below epsilon threshold
     * Should still create item for amounts just above epsilon
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testShippingCostJustAboveEpsilon(): void
    {
        // Arrange: €0.0002 (above epsilon of 0.0001)
        $mockCart = $this->createMock(Cart::class);
        $mockCarrier = $this->createMock(Carrier::class);
        $mockCarrier->name = 'Micro Shipping';

        $cartSummary = [
            'total_shipping_tax_exc' => 0.0002,
            'total_shipping' => 0.0002,
            'carrier' => $mockCarrier
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertGreaterThan(0, $result[0]->getUnitPrice()->getAmount());
    }

    /**
     * Test very large shipping cost
     * Scenario: Heavy/bulky items, international express shipping
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testVeryLargeShippingCost(): void
    {
        // Arrange: €500 shipping (heavy machinery, international)
        $mockCart = $this->createMock(Cart::class);
        $mockCarrier = $this->createMock(Carrier::class);
        $mockCarrier->name = 'Heavy Freight Shipping';

        $cartSummary = [
            'total_shipping_tax_exc' => 500.00,
            'total_shipping' => 605.00, // 500 + 21% = 605
            'carrier' => $mockCarrier
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(500.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEqualsWithDelta(21.00, $result[0]->getTaxRate(), 0.01);
    }

    /**
     * Test shipping without carrier name (fallback to translation)
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testShippingWithoutCarrierName(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_shipping_tax_exc' => 5.00,
            'total_shipping' => 6.05,
            'carrier' => null // No carrier
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Shipping', $result[0]->getName());
    }

    /**
     * Test quantity is always 1 for shipping item
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testShippingQuantityIsAlwaysOne(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $mockCarrier = $this->createMock(Carrier::class);
        $mockCarrier->name = 'Test Shipping';

        $cartSummary = [
            'total_shipping_tax_exc' => 10.00,
            'total_shipping' => 12.10,
            'carrier' => $mockCarrier
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]->getQuantity());
    }

    /**
     * Test shipping with fractional tax rate
     * Scenario: Complex tax calculations
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testShippingWithFractionalTaxRate(): void
    {
        // Arrange: 19.5% VAT (some countries use this)
        $mockCart = $this->createMock(Cart::class);
        $mockCarrier = $this->createMock(Carrier::class);
        $mockCarrier->name = 'Special Rate Shipping';

        $cartSummary = [
            'total_shipping_tax_exc' => 10.00,
            'total_shipping' => 11.95, // 10.00 + 19.5% = 11.95
            'carrier' => $mockCarrier
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(10.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEqualsWithDelta(19.50, $result[0]->getTaxRate(), 0.01);
    }

    /**
     * Test shipping precision with problematic float numbers
     * Edge case: Numbers that cause floating point precision issues
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testShippingPrecisionWithProblematicFloats(): void
    {
        // Arrange: Using numbers known to cause precision issues
        $mockCart = $this->createMock(Cart::class);
        $mockCarrier = $this->createMock(Carrier::class);
        $mockCarrier->name = 'Precision Test Shipping';

        $cartSummary = [
            'total_shipping_tax_exc' => 3.33,
            'total_shipping' => 4.03, // 3.33 × 1.21 ≈ 4.0293
            'carrier' => $mockCarrier
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEqualsWithDelta(3.33, $result[0]->getUnitPrice()->getAmount() / 100, 0.01);

        // Tax calculation should handle precision correctly
        // (4.03 - 3.33) / 3.33 * 100 ≈ 21%
        $this->assertEqualsWithDelta(21.00, $result[0]->getTaxRate(), 1.0);
    }

    /**
     * Test division by zero protection with epsilon
     * Should return 0% tax rate when shipping cost is at or below epsilon
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testDivisionByZeroProtection(): void
    {
        // Arrange: Shipping at epsilon threshold
        $mockCart = $this->createMock(Cart::class);
        $mockCarrier = $this->createMock(Carrier::class);
        $mockCarrier->name = 'Near-Zero Shipping';

        $cartSummary = [
            'total_shipping_tax_exc' => 0.00005, // Below epsilon (0.0001)
            'total_shipping' => 0.00006,
            'carrier' => $mockCarrier
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // Tax rate should be 0 (protected by epsilon comparison)
        $this->assertEquals(0.00, $result[0]->getTaxRate());
    }

    /**
     * Test currency code is passed correctly
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testCurrencyCodeIsCorrect(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $mockCarrier = $this->createMock(Carrier::class);
        $mockCarrier->name = 'Test Shipping';

        $cartSummary = [
            'total_shipping_tax_exc' => 10.00,
            'total_shipping' => 12.10,
            'carrier' => $mockCarrier
        ];

        // Act - Test with different currencies
        $resultEUR = $this->builder->build($mockCart, $cartSummary, 'EUR');
        $resultUSD = $this->builder->build($mockCart, $cartSummary, 'USD');
        $resultGBP = $this->builder->build($mockCart, $cartSummary, 'GBP');

        // Assert
        $this->assertEquals('EUR', $resultEUR[0]->getUnitPrice()->getCurrency());
        $this->assertEquals('USD', $resultUSD[0]->getUnitPrice()->getCurrency());
        $this->assertEquals('GBP', $resultGBP[0]->getUnitPrice()->getCurrency());
    }

    /**
     * Test express/premium shipping with higher cost
     * Scenario: Same-day, next-day delivery services
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\ShippingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testExpressShipping(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $mockCarrier = $this->createMock(Carrier::class);
        $mockCarrier->name = 'Express 24h Delivery';

        $cartSummary = [
            'total_shipping_tax_exc' => 25.00,
            'total_shipping' => 30.25, // 25 + 21% = 30.25
            'carrier' => $mockCarrier
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(25.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEquals('Express 24h Delivery', $result[0]->getName());
        $this->assertEqualsWithDelta(21.00, $result[0]->getTaxRate(), 0.01);
    }
}
