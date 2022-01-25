<?php declare(strict_types=1);

namespace MultiSafepay\Tests\Services;

use PHPUnit\Framework\TestCase;
use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use MultiSafepay\ValueObject\Money;

class MoneyHelperTest extends TestCase
{
    /**
     * @covers \MultiSafepay\PrestaShop\Helper\MoneyHelper::createMoney
     */
    public function testCreateMoneyReturnInstanceOfMoney()
    {
        $output = MoneyHelper::createMoney(525);
        self::assertInstanceOf(Money::class, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\MoneyHelper::priceToCents
     */
    public function testPriceToCents()
    {
        $output = MoneyHelper::priceToCents(34.75);
        self::assertEquals(3475, $output);
    }
}
