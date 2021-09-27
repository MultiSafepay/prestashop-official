<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\Helper;

use MultiSafepay\ValueObject\Money;

/**
 * Class MoneyHelper
 *
 */
class MoneyHelper
{
    public const DEFAULT_CURRENCY_CODE = 'EUR';

    /**
     * @param float  $amount
     * @param string $currencyCode
     * @return Money
     */
    public static function createMoney(float $amount, string $currencyCode = self::DEFAULT_CURRENCY_CODE): Money
    {
        return new Money(self::priceToCents($amount), $currencyCode);
    }

    /**
     * @param float $price
     * @return float
     */
    private static function priceToCents(float $price): float
    {
        return $price * 100;
    }
}
