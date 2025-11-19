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

use MultiSafepay\PrestaShop\Helper\ConfigHelper;
use PHPUnit\Framework\TestCase;

class ConfigHelperTest extends TestCase
{
    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     */
    public function testSettingToIntArrayWithValidJsonArray(): void
    {
        $input = '[5,2,3,4]';
        $expected = [5, 2, 3, 4];
        $output = ConfigHelper::settingToIntArray($input);

        self::assertIsArray($output);
        self::assertEquals($expected, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     */
    public function testSettingToIntArrayWithStringNumbers(): void
    {
        $input = '["5","2","3","4"]';
        $expected = [5, 2, 3, 4];
        $output = ConfigHelper::settingToIntArray($input);

        self::assertIsArray($output);
        self::assertEquals($expected, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     */
    public function testSettingToIntArrayWithEmptyString(): void
    {
        $input = '';
        $expected = [];
        $output = ConfigHelper::settingToIntArray($input);

        self::assertIsArray($output);
        self::assertEquals($expected, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     */
    public function testSettingToIntArrayWithInvalidJson(): void
    {
        $input = 'invalid json string';
        $expected = [];
        $output = ConfigHelper::settingToIntArray($input);

        self::assertIsArray($output);
        self::assertEquals($expected, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     */
    public function testSettingToIntArrayWithNullJsonValue(): void
    {
        $input = 'null';
        $expected = [];
        $output = ConfigHelper::settingToIntArray($input);

        self::assertIsArray($output);
        self::assertEquals($expected, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     */
    public function testSettingToIntArrayWithEmptyJsonArray(): void
    {
        $input = '[]';
        $expected = [];
        $output = ConfigHelper::settingToIntArray($input);

        self::assertIsArray($output);
        self::assertEquals($expected, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     */
    public function testSettingToIntArrayWithSingleValue(): void
    {
        $input = '[42]';
        $expected = [42];
        $output = ConfigHelper::settingToIntArray($input);

        self::assertIsArray($output);
        self::assertEquals($expected, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     */
    public function testSettingToIntArrayWithMixedIntegersAndStrings(): void
    {
        $input = '[1,"2",3,"4",5]';
        $expected = [1, 2, 3, 4, 5];
        $output = ConfigHelper::settingToIntArray($input);

        self::assertIsArray($output);
        self::assertEquals($expected, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     */
    public function testSettingToIntArrayWithNegativeNumbers(): void
    {
        $input = '[-1,-2,-3]';
        $expected = [-1, -2, -3];
        $output = ConfigHelper::settingToIntArray($input);

        self::assertIsArray($output);
        self::assertEquals($expected, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     */
    public function testSettingToIntArrayWithZeros(): void
    {
        $input = '[0,0,0]';
        $expected = [0, 0, 0];
        $output = ConfigHelper::settingToIntArray($input);

        self::assertIsArray($output);
        self::assertEquals($expected, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     */
    public function testSettingToIntArrayWithLargeNumbers(): void
    {
        $input = '[999999,1000000,2147483647]';
        $expected = [999999, 1000000, 2147483647];
        $output = ConfigHelper::settingToIntArray($input);

        self::assertIsArray($output);
        self::assertEquals($expected, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     * @dataProvider invalidJsonProvider
     */
    public function testSettingToIntArrayWithVariousInvalidJsonInputs(string $input): void
    {
        $output = ConfigHelper::settingToIntArray($input);

        self::assertIsArray($output);
        self::assertEmpty($output);
    }

    /**
     * Data provider for invalid JSON strings
     */
    public function invalidJsonProvider(): array
    {
        return [
            'malformed json' => ['[1,2,3'],
            'json object instead of array' => ['{"key":"value"}'],
            'json string instead of array' => ['"string"'],
            'json number instead of array' => ['123'],
            'json boolean instead of array' => ['true'],
            'incomplete json' => ['[1,2,'],
            'extra characters' => ['[1,2,3]extra'],
        ];
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     */
    public function testSettingToIntArrayWithFloatsConvertedToInts(): void
    {
        $input = '[1.5,2.9,3.1]';
        $expected = [1, 2, 3];
        $output = ConfigHelper::settingToIntArray($input);

        self::assertIsArray($output);
        self::assertEquals($expected, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     */
    public function testSettingToIntArrayPreventsPHP8FatalError(): void
    {
        // This test verifies the fix for PHP 8+ Fatal Error when json_decode returns null
        $input = 'not valid json at all';

        // Should not throw Fatal Error in PHP 8+
        $output = ConfigHelper::settingToIntArray($input);

        self::assertIsArray($output);
        self::assertEmpty($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     */
    public function testSettingToIntArrayWithWhitespaceInJson(): void
    {
        $input = '[ 1 , 2 , 3 , 4 ]';
        $expected = [1, 2, 3, 4];
        $output = ConfigHelper::settingToIntArray($input);

        self::assertIsArray($output);
        self::assertEquals($expected, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\ConfigHelper::settingToIntArray
     */
    public function testSettingToIntArrayReturnsSameTypesConsistently(): void
    {
        $input = '[1,2,3]';
        $output = ConfigHelper::settingToIntArray($input);

        foreach ($output as $value) {
            self::assertIsInt($value);
        }
    }
}
