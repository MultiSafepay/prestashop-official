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

namespace MultiSafepay\Tests\Helper;

use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use MultiSafepay\ValueObject\Money;
use PHPUnit\Framework\TestCase;

class MoneyHelperTest extends TestCase
{
    /**
     * @covers \MultiSafepay\PrestaShop\Helper\MoneyHelper::createMoney
     */
    public function testCreateMoneyReturnInstanceOfMoney(): void
    {
        $output = MoneyHelper::createMoney(525);
        self::assertInstanceOf(Money::class, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\MoneyHelper::priceToCents
     * @dataProvider priceProvider
     */
    public function testPriceToCents($input, $expected): void
    {
        $output = MoneyHelper::priceToCents($input);
        self::assertEquals($expected, $output);
    }

    /**
     * @return array
     */
    public function priceProvider(): array
    {
        return [
            'regular price' => [34.75, 3475],
            'zero price' => [0.00, 0],
            'large price' => [999.99, 99999],
            'integer price' => [50, 5000],
            'small price' => [0.01, 1]
        ];
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\MoneyHelper::createMoney
     */
    public function testCreateMoneyWithZeroAmount(): void
    {
        $output = MoneyHelper::createMoney(0);
        self::assertInstanceOf(Money::class, $output);
        self::assertEquals(0, $output->getAmount());
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\MoneyHelper::createMoney
     */
    public function testCreateMoneyWithCustomCurrency(): void
    {
        $output = MoneyHelper::createMoney(10.99, 'EUR');
        self::assertInstanceOf(Money::class, $output);
        self::assertEquals(1099, $output->getAmount());
        self::assertEquals('EUR', $output->getCurrency());
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\MoneyHelper::priceToCents
     */
    public function testPriceToCentsWithNegativeValue(): void
    {
        $output = MoneyHelper::priceToCents(-15.75);
        self::assertEquals(-1575, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\MoneyHelper::createMoney
     */
    public function testCreateMoneyWithNegativeAmount(): void
    {
        $output = MoneyHelper::createMoney(-25.50);
        self::assertInstanceOf(Money::class, $output);
        self::assertEquals(-2550, $output->getAmount());
        self::assertEquals(MoneyHelper::DEFAULT_CURRENCY_CODE, $output->getCurrency());
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\MoneyHelper::priceToCents
     */
    public function testPriceToCentsWithHighPrecision(): void
    {
        $output = MoneyHelper::priceToCents(10.999);
        self::assertEquals(1099.9, $output);
    }
}
