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
use MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultisafepayOfficial;

/**
 * Test DiscountItemBuilder for all discount scenarios
 *
 * This test suite validates discount calculation robustness against:
 * - Cart rules (fixed amount, percentage, free shipping)
 * - Group discounts
 * - Specific prices
 * - Multiple discounts combined
 * - Different tax rates on discounted products
 * - Edge cases (very small discounts, very large discounts)
 * - Precision with PrestaShop's tax calculations
 */
class DiscountItemBuilderTest extends BaseMultiSafepayTest
{
    /**
     * @var DiscountItemBuilder
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
            ->willReturn('Discount');

        $this->builder = new DiscountItemBuilder($mockModule);
    }

    /**
     * Test no discount scenario
     * Should return empty array when no discount is applied
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testNoDiscount(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 0.0,
            'total_discounts' => 0.0
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertEmpty($result);
    }

    /**
     * Test very small discount (edge case for epsilon)
     * Should return empty array when discount is below epsilon threshold
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testVerySmallDiscountBelowEpsilon(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 0.00005, // Below epsilon (0.0001)
            'total_discounts' => 0.00006
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertEmpty($result);
    }

    /**
     * Test basic discount with 21% VAT
     * Standard scenario: €10 discount on products with 21% VAT
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBasicDiscountWith21PercentVAT(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 10.00,
            'total_discounts' => 12.10 // 10.00 + 21% = 12.10
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Discount', $result[0]->getName());
        $this->assertEquals(-10.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEquals(21.00, $result[0]->getTaxRate());
        $this->assertEquals(1, $result[0]->getQuantity());
    }

    /**
     * Test discount with 10% VAT (reduced rate)
     * Scenario: Discount on food products
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testDiscountWith10PercentVAT(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 20.00,
            'total_discounts' => 22.00 // 20.00 + 10% = 22.00
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(-20.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEquals(10.00, $result[0]->getTaxRate());
    }

    /**
     * Test discount with 4% VAT (super reduced rate)
     * Scenario: Discount on essential goods
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testDiscountWith4PercentVAT(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 100.00,
            'total_discounts' => 104.00 // 100.00 + 4% = 104.00
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(-100.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEquals(4.00, $result[0]->getTaxRate());
    }

    /**
     * Test discount with 0% VAT
     * Scenario: Tax-exempt products with discount
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testDiscountWith0PercentVAT(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 50.00,
            'total_discounts' => 50.00 // No tax
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(-50.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEquals(0.00, $result[0]->getTaxRate());
    }

    /**
     * Test large discount (>€1000)
     * Critical scenario: Big sales, B2B discounts
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testLargeDiscount(): void
    {
        // Arrange: €5000 discount with 21% VAT
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 5000.00,
            'total_discounts' => 6050.00 // 5000 + 21% = 6050
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(-5000.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEquals(21.00, $result[0]->getTaxRate());
    }

    /**
     * Test discount with mixed VAT rates (weighted average)
     * Critical scenario: Discount applied to products with different tax rates
     * PrestaShop calculates the exact tax, we derive the rate from it
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testDiscountWithMixedVATRates(): void
    {
        // Arrange: Discount on cart with 21% and 10% products
        // PrestaShop has already calculated the exact discount amounts
        // Example: €100 discount distributed proportionally
        // If 60% products are 21% VAT and 40% are 10% VAT:
        // Weighted tax = (60 × 21 + 40 × 10) / 100 = 16.6%
        // Discount without tax: €100
        // Discount with tax: €100 × 1.166 = €116.60
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 100.00,
            'total_discounts' => 116.60
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(-100.00, $result[0]->getUnitPrice()->getAmount() / 100);

        // Tax rate should be calculated: (116.60 - 100.00) / 100.00 * 100 = 16.60%
        $this->assertEqualsWithDelta(16.60, $result[0]->getTaxRate(), 0.01);
    }

    /**
     * Test 100% discount (free cart)
     * Edge case: Promotional codes giving entire cart for free
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testFullDiscount100Percent(): void
    {
        // Arrange: Cart total €242 (€200 + 21% VAT), discount 100%
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 200.00,
            'total_discounts' => 242.00
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(-200.00, $result[0]->getUnitPrice()->getAmount() / 100);
        $this->assertEquals(21.00, $result[0]->getTaxRate());
    }

    /**
     * Test discount with fractional tax rate
     * Scenario: Complex scenarios resulting in non-standard tax rates
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testDiscountWithFractionalTaxRate(): void
    {
        // Arrange: Discount resulting in 19.37% effective tax rate
        // This can happen with complex mixed-rate scenarios
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 100.00,
            'total_discounts' => 119.37
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(-100.00, $result[0]->getUnitPrice()->getAmount() / 100);

        // Tax rate should be rounded to 2 decimals: 19.37%
        $this->assertEquals(19.37, $result[0]->getTaxRate());
    }

    /**
     * Test discount precision with problematic float numbers
     * Edge case: Numbers that cause floating point precision issues
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testDiscountPrecisionWithProblematicFloats(): void
    {
        // Arrange: Using numbers known to cause precision issues
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 33.33,
            'total_discounts' => 40.3293 // 33.33 × 1.21 = 40.3293
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(-33.33, $result[0]->getUnitPrice()->getAmount() / 100);

        // Should handle precision correctly
        // (40.3293 - 33.33) / 33.33 * 100 = 21.00%
        $this->assertEqualsWithDelta(21.00, $result[0]->getTaxRate(), 0.01);
    }

    /**
     * Test discount amount is negative in MultiSafepay
     * Verify that discount is sent as negative value
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testDiscountIsNegativeValue(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 15.00,
            'total_discounts' => 18.15
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $discountAmount = $result[0]->getUnitPrice()->getAmount() / 100;

        // Must be negative
        $this->assertLessThan(0, $discountAmount);
        $this->assertEquals(-15.00, $discountAmount);
    }

    /**
     * Test merchant item ID is set correctly
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testMerchantItemIdIsCorrect(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 10.00,
            'total_discounts' => 12.10
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Discount', $result[0]->getMerchantItemId());
    }

    /**
     * Test quantity is always 1 for discount item
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testDiscountQuantityIsAlwaysOne(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 50.00,
            'total_discounts' => 60.50
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]->getQuantity());
    }

    /**
     * Test rounding of tax rate to 2 decimals
     * Should prevent sending rates like 21.3333333% to payment gateway
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testTaxRateRoundedTo2Decimals(): void
    {
        // Arrange: Rate that would be 21.333333%
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 100.00,
            'total_discounts' => 121.33 // Would result in 21.33% (not 21.3333333%)
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $taxRate = $result[0]->getTaxRate();

        // Should be rounded to 2 decimals
        $this->assertEquals(21.33, $taxRate);

        // Verify it's actually rounded (no more than 2 decimal places)
        $this->assertEquals(round($taxRate, 2), $taxRate);
    }

    /**
     * Test extreme rounding scenario
     * Edge case: 21.999999% should round to 22.00%
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testExtremeTaxRateRounding(): void
    {
        // Arrange: Very close to 22%
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 100.00,
            'total_discounts' => 121.999 // 21.999%
        ];

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(22.00, $result[0]->getTaxRate());
    }

    /**
     * Test currency code is passed correctly
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testCurrencyCodeIsCorrect(): void
    {
        // Arrange
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 10.00,
            'total_discounts' => 12.10
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
     * Test discount with Billink gateway - tax rate within tolerance (20.97% rounds to 21%)
     * Scenario: Mixed cart with products at different VAT rates, weighted average is 20.97%
     * Critical: 20.97% is within 0.05 of allowed rate 21, should round to 21%
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBillinkGatewayWithMixedVATDiscount(): void
    {
        // Arrange: Discount with weighted average tax rate of 20.97%
        // Example: Cart with products weighted toward 21% VAT
        // 20.97% is very close to 21% (within 0.05 tolerance)
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 100.00,
            'total_discounts' => 120.97 // 100 + 20.97% = 120.97
        ];

        // Set Billink gateway
        $this->builder->setCurrentGatewayCode('BILLINK');

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // 20.97% is within 0.05 tolerance of rate 21, so round(20.97) = 21
        $this->assertEquals(21, $result[0]->getTaxRate());
    }

    /**
     * Test discount with Billink gateway - standard VAT rate (21%)
     * Scenario: Discount on cart with single 21% VAT rate
     * Critical: 21% is exactly an allowed rate, should stay 21%
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBillinkGatewayWithStandardVAT(): void
    {
        // Arrange: Discount with standard 21% VAT
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 50.00,
            'total_discounts' => 60.50 // 50 + 21% = 60.50
        ];

        // Set Billink gateway
        $this->builder->setCurrentGatewayCode('BILLINK');

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // 21% is exactly an allowed rate, round(21) = 21
        $this->assertEquals(21, $result[0]->getTaxRate());
    }

    /**
     * Test discount with Billink gateway - near allowed rate (20.96% rounds to 21%)
     * Scenario: Complex mixed cart with weighted average 20.96%
     * Critical: 20.96% is within 0.05 of allowed rate 21, should round to 21%
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBillinkGatewayWithComplexMixedVAT(): void
    {
        // Arrange: Complex discount scenario
        // Example: Products with 19%, 20% VAT distributed proportionally
        // Results in ~19.03% weighted average
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 100.00,
            'total_discounts' => 119.03 // 100 + 19.03% = 119.03
        ];

        // Set Billink gateway
        $this->builder->setCurrentGatewayCode('BILLINK');

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // 19.03% is within 0.05 tolerance of rate 19, round(19.03) = 19
        $this->assertEquals(19, $result[0]->getTaxRate());
    }

    /**
     * Test discount with Billink gateway - rate outside tolerance (15% stays 15%)
     * Scenario: Tax rate that's NOT within 0.05 of any allowed rate
     * Critical: 15% is not within 0.05 of 16 (diff=1), should return 15% unchanged
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBillinkGatewayWithRateOutsideTolerance(): void
    {
        // Arrange: Discount with 15% tax rate (outside tolerance of all allowed rates)
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 100.00,
            'total_discounts' => 115.00 // 100 + 15% = 115
        ];

        // Set Billink gateway
        $this->builder->setCurrentGatewayCode('BILLINK');

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // 15% is outside 0.05 tolerance (nearest is 16, diff=1.0), returns 15% unchanged
        $this->assertEquals(15.0, $result[0]->getTaxRate());
    }

    /**
     * Test discount with Billink gateway - 0% tax (tax-exempt)
     * Scenario: Discount on tax-exempt products
     * Critical: 0% should remain 0%
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBillinkGatewayWithZeroTax(): void
    {
        // Arrange: Discount with 0% tax
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 100.00,
            'total_discounts' => 100.00 // No tax
        ];

        // Set Billink gateway
        $this->builder->setCurrentGatewayCode('BILLINK');

        // Act
        $result = $this->builder->build($mockCart, $cartSummary, 'EUR');

        // Assert
        $this->assertCount(1, $result);
        // 0% is exactly an allowed rate, returns 0%
        $this->assertEquals(0.0, $result[0]->getTaxRate());
    }

    /**
     * Test discount with Billink gateway - edge case 16.6% (outside tolerance after rounding)
     * Scenario: Mixed VAT cart resulting in 16.6% weighted average
     * Critical: After rounding to 2 decimals (16.60%), it's NOT within 0.05 tolerance of any allowed rate
     * Expected: Should return 16.60% unchanged (not rounded to 17)
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBillinkGatewayWithEdgeCase16Point6Percent(): void
    {
        // Arrange: Discount with 16.6% tax rate
        // Example: 60% products with 21% VAT, 40% with 10% VAT
        // Weighted average: (60*21 + 40*10)/100 = 16.6%
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 100.00,
            'total_discounts' => 116.60 // 100 + 16.6% = 116.60
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
        $this->assertEquals(16.60, $result[0]->getTaxRate());
    }

    /**
     * Test discount with Billink gateway - edge case 20.84% (outside tolerance after rounding)
     * Scenario: Complex mixed VAT cart resulting in 20.84% weighted average
     * Critical: After rounding to 2 decimals (20.84%), it's NOT within 0.05 tolerance of 21
     * Expected: Should return 20.84% unchanged (not rounded to 21)
     *
     * @covers \MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder\DiscountItemBuilder::build
     * @throws InvalidArgumentException
     */
    public function testBillinkGatewayWithEdgeCase20Point84Percent(): void
    {
        // Arrange: Discount with 20.84% tax rate
        // Example: Products with varying VAT rates distributed to result in ~20.84%
        $mockCart = $this->createMock(Cart::class);
        $cartSummary = [
            'total_discounts_tax_exc' => 100.00,
            'total_discounts' => 120.84 // 100 + 20.84% = 120.84
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
        $this->assertEquals(20.84, $result[0]->getTaxRate());
    }
}
