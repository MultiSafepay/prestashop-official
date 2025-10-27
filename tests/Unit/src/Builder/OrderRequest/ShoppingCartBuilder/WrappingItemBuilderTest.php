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
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultisafepayOfficial;

/**
 * Unit tests for WrappingItemBuilder
 * Tests gift wrapping functionality for PrestaShop carts
 *
 * @package MultiSafepay\Tests\Builder\OrderRequest\ShoppingCartBuilder
 */
class WrappingItemBuilderTest extends BaseMultiSafepayTest
{
    /**
     * @var WrappingItemBuilder
     */
    private $builder;

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

        $this->builder = new WrappingItemBuilder($mockModule);
    }

    /**
     * Test basic gift wrapping with standard price (no tax)
     * Scenario: Customer adds gift wrapping to cart without tax
     * Critical: Wrapping item must have correct price and quantity
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBasicGiftWrapping(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 2.50, // Standard wrapping price with tax
            'total_wrapping_tax_exc' => 2.50, // Same price = no tax
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result, 'Should create one wrapping item');
        $this->assertEquals('Wrapping', $result[0]->getName());
        $this->assertEquals('Wrapping', $result[0]->getMerchantItemId());
        $this->assertEquals(1, $result[0]->getQuantity());
        $this->assertEquals(250, $result[0]->getUnitPrice()->getAmount()); // €2.50 in cents
        $this->assertEquals(0.0, $result[0]->getTaxRate(), 'Wrapping should have 0% tax when prices are equal');
    }

    /**
     * Test cart without gift wrapping
     * Scenario: Customer doesn't select gift wrapping
     * Critical: No wrapping item should be created
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testNoGiftWrapping(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 0, // No wrapping
            'total_wrapping_tax_exc' => 0,
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(0, $result, 'Should not create wrapping item when total_wrapping is 0');
    }

    /**
     * Test cart with negative wrapping value (edge case)
     * Scenario: Invalid data with negative wrapping
     * Critical: Should not create item with negative value
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testNegativeWrappingValue(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => -5.00, // Invalid negative value
            'total_wrapping_tax_exc' => -5.00,
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(0, $result, 'Should not create wrapping item with negative value');
    }

    /**
     * Test cart without wrapping key in summary
     * Scenario: Cart summary doesn't have total_wrapping key
     * Critical: Should handle missing key gracefully
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testMissingWrappingKey(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = []; // No wrapping key at all

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(0, $result, 'Should handle missing total_wrapping key gracefully');
    }

    /**
     * Test premium gift wrapping (higher price)
     * Scenario: Merchant offers premium wrapping option
     * Critical: Should handle different price points
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testPremiumGiftWrapping(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 7.95, // Premium wrapping
            'total_wrapping_tax_exc' => 7.95,
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(795, $result[0]->getUnitPrice()->getAmount()); // €7.95 in cents
    }

    /**
     * Test very small wrapping price (€0.01)
     * Scenario: Symbolic wrapping charge
     * Critical: Should handle minimum price correctly
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testMinimumWrappingPrice(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 0.01, // Minimum price
            'total_wrapping_tax_exc' => 0.01,
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]->getUnitPrice()->getAmount()); // €0.01 in cents
    }

    /**
     * Test wrapping with different currency (GBP)
     * Scenario: UK merchant with gift wrapping
     * Critical: Currency should be correctly set
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testWrappingWithDifferentCurrency(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 3.50,
            'total_wrapping_tax_exc' => 3.50,
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'GBP');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('GBP', $result[0]->getUnitPrice()->getCurrency());
        $this->assertEquals(350, $result[0]->getUnitPrice()->getAmount()); // £3.50 in pence
    }

    /**
     * Test wrapping with fractional cents
     * Scenario: Wrapping price with rounding required (€2.495)
     * Critical: Should round correctly to 2 decimals
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testWrappingWithFractionalCents(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 2.495, // Should round to €2.50
            'total_wrapping_tax_exc' => 2.495,
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // MoneyHelper should handle rounding correctly
        $this->assertEqualsWithDelta(
            250,
            $result[0]->getUnitPrice()->getAmount(),
            1,
            'Should round fractional cents correctly'
        );
    }

    /**
     * Test that wrapping always has quantity = 1
     * Scenario: Multiple products in cart with wrapping
     * Critical: Wrapping is always a single item, regardless of cart size
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testWrappingAlwaysHasQuantityOne(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 5.00,
            'total_wrapping_tax_exc' => 5.00,
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertEquals(
            1,
            $result[0]->getQuantity(),
            'Wrapping quantity should always be 1, regardless of number of products'
        );
    }

    /**
     * Test wrapping with no tax (when prices are equal)
     * Scenario: Verify wrapping has no tax applied when tax_exc equals tax_inc
     * Critical: Should correctly detect when there's no tax
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testWrappingHasZeroTaxRate(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 4.50,
            'total_wrapping_tax_exc' => 4.50, // Same price = no tax
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertEquals(
            0.0,
            $result[0]->getTaxRate(),
            'Wrapping should have 0% tax rate when prices are equal'
        );
    }

    /**
     * Test wrapping with 21% tax (standard VAT rate)
     * Scenario: Shop has PS_GIFT_WRAPPING_TAX_RULES_GROUP configured
     * Critical: Should correctly calculate tax rate from PrestaShop's values
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testWrappingWithTax(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 3.025, // €2.50 + 21% VAT
            'total_wrapping_tax_exc' => 2.50,
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(250, $result[0]->getUnitPrice()->getAmount()); // €2.50 without tax
        $this->assertEqualsWithDelta(21.0, $result[0]->getTaxRate(), 0.1, 'Should calculate 21% tax rate');
    }

    /**
     * Test wrapping with Billink gateway - tax rate within tolerance (20.98% rounds to 21%)
     * Scenario: Gift wrapping with 20.98% VAT, Billink payment
     * Critical: 20.98% is within 0.05 of allowed rate 21, should round to 21%
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testWrappingWithBillinkGateway(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 3.0245, // €2.50 + 20.98% VAT = €3.0245
            'total_wrapping_tax_exc' => 2.50,
        ];

        // Set Billink gateway
        $this->builder->setCurrentGatewayCode('BILLINK');

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // 20.98% is within 0.05 tolerance of rate 21, round(20.98) = 21
        $this->assertEquals(21.0, $result[0]->getTaxRate(), 'Billink should round 20.98% to 21%');
    }

    /**
     * Test wrapping with Billink gateway - standard 21% VAT
     * Scenario: Gift wrapping with exactly 21% VAT, Billink payment
     * Critical: 21% is exactly an allowed rate, should stay 21%
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testWrappingWithBillinkGatewayStandardVAT(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 3.025, // €2.50 + 21% VAT = €3.025
            'total_wrapping_tax_exc' => 2.50,
        ];

        // Set Billink gateway
        $this->builder->setCurrentGatewayCode('BILLINK');

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // 21% is exactly an allowed rate, round(21) = 21
        $this->assertEquals(21.0, $result[0]->getTaxRate(), 'Billink should keep 21% as 21%');
    }

    /**
     * Test wrapping with Billink gateway - 0% tax (tax-exempt)
     * Scenario: Gift wrapping without tax, Billink payment
     * Critical: 0% should remain 0%
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testWrappingWithBillinkGatewayZeroTax(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 2.50, // No tax
            'total_wrapping_tax_exc' => 2.50,
        ];

        // Set Billink gateway
        $this->builder->setCurrentGatewayCode('BILLINK');

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // 0% is exactly an allowed rate, returns 0%
        $this->assertEquals(0.0, $result[0]->getTaxRate(), 'Billink should keep 0% tax as 0%');
    }

    /**
     * Test wrapping with Billink gateway - tax rate near 19% (19.04% rounds to 19%)
     * Scenario: Gift wrapping with 19.04% VAT (close to 19%), Billink payment
     * Critical: 19.04% is within 0.05 of allowed rate 19, should round to 19%
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testWrappingWithBillinkGatewayNearNineteenPercent(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 5.952, // €5.00 + 19.04% VAT = €5.952
            'total_wrapping_tax_exc' => 5.00,
        ];

        // Set Billink gateway
        $this->builder->setCurrentGatewayCode('BILLINK');

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // 19.04% is within 0.05 tolerance of rate 19, round(19.04) = 19
        $this->assertEquals(19.0, $result[0]->getTaxRate(), 'Billink should round 19.04% to 19%');
    }

    /**
     * Test wrapping with Billink gateway - tax rate outside tolerance (15% stays 15%)
     * Scenario: Gift wrapping with 15% VAT (not near any allowed rate), Billink payment
     * Critical: 15% is outside 0.05 tolerance, should return 15% unchanged
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testWrappingWithBillinkGatewayOutsideTolerance(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 2.875, // €2.50 + 15% VAT = €2.875
            'total_wrapping_tax_exc' => 2.50,
        ];

        // Set Billink gateway
        $this->builder->setCurrentGatewayCode('BILLINK');

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // 15% is outside 0.05 tolerance (nearest is 16, diff=1.0), returns 15% unchanged
        $this->assertEquals(15.0, $result[0]->getTaxRate(), 'Billink should keep 15% unchanged (outside tolerance)');
    }

    /**
     * Test wrapping with Billink gateway - edge case 20.84% (outside tolerance after rounding)
     * Scenario: Gift wrapping with 20.84% VAT, Billink payment
     * Critical: After rounding to 2 decimals (20.84%), it's NOT within 0.05 tolerance of 21
     * Expected: Should return 20.84% unchanged (not rounded to 21)
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testWrappingWithBillinkGatewayEdgeCase20Point84Percent(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 3.021, // €2.50 + 20.84% VAT = €3.021
            'total_wrapping_tax_exc' => 2.50,
        ];

        // Set Billink gateway
        $this->builder->setCurrentGatewayCode('BILLINK');

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // After rounding to 2 decimals: 20.84%
        // Distance to 20: |20.84 - 20| = 0.84 > 0.05
        // Distance to 21: |20.84 - 21| = 0.16 > 0.05
        // Should return 20.84% unchanged (outside tolerance)
        $this->assertEquals(20.84, $result[0]->getTaxRate(), 'Billink should keep 20.84% as is (outside 0.05 tolerance)');
    }

    /**
     * Test wrapping with Billink gateway - edge case 16.6% (outside tolerance after rounding)
     * Scenario: Gift wrapping with 16.6% VAT, Billink payment
     * Critical: After rounding to 2 decimals (16.60%), it's NOT within 0.05 tolerance of any allowed rate
     * Expected: Should return 16.60% unchanged (not rounded to 17)
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\WrappingItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testWrappingWithBillinkGatewayEdgeCase16Point6Percent(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);

        $cartSummary = [
            'total_wrapping' => 2.915, // €2.50 + 16.6% VAT = €2.915
            'total_wrapping_tax_exc' => 2.50,
        ];

        // Set Billink gateway
        $this->builder->setCurrentGatewayCode('BILLINK');

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // After rounding to 2 decimals: 16.60%
        // Distance to 16: |16.60 - 16| = 0.60 > 0.05
        // Distance to 17: |16.60 - 17| = 0.40 > 0.05
        // Should return 16.60% unchanged (outside tolerance)
        $this->assertEquals(16.60, $result[0]->getTaxRate(), 'Billink should keep 16.60% unchanged (outside tolerance)');
    }
}
