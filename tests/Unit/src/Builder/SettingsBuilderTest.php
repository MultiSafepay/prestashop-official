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

namespace MultiSafepay\Tests\Builder;

use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultisafepayOfficial;
use PHPUnit\Framework\MockObject\MockObject;

class SettingsBuilderTest extends BaseMultiSafepayTest
{
    /**
     * @var SettingsBuilder
     */
    private $settingsBuilder;

    /**
     * @var MockObject
     */
    private $mockModule;

    public function setUp(): void
    {
        parent::setUp();

        /** @var MultisafepayOfficial $mockModule */
        $mockModule = $this->createMock(MultisafepayOfficial::class);
        $this->mockModule = $mockModule;
        $this->settingsBuilder = new SettingsBuilder($mockModule);
    }

    /**
     * Test that class constants are properly defined
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder
     */
    public function testConstants(): void
    {
        $this->assertEquals('seconds', SettingsBuilder::SECONDS);
        $this->assertEquals('hours', SettingsBuilder::HOURS);
        $this->assertEquals('days', SettingsBuilder::DAYS);
        $this->assertEquals('SettingsBuilder', SettingsBuilder::CLASS_NAME);
        $this->assertStringContainsString('github.com', SettingsBuilder::MULTISAFEPAY_RELEASES_GITHUB_URL);
    }

    /**
     * Test getConfigFieldsAndDefaultValues method
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder::getConfigFieldsAndDefaultValues
     */
    public function testGetConfigFieldsAndDefaultValues(): void
    {
        $configFields = SettingsBuilder::getConfigFieldsAndDefaultValues();

        $this->assertIsArray($configFields);
        $this->assertNotEmpty($configFields);

        // Test some specific expected keys
        $this->assertArrayHasKey('MULTISAFEPAY_OFFICIAL_TEST_MODE', $configFields);
        $this->assertArrayHasKey('MULTISAFEPAY_OFFICIAL_API_KEY', $configFields);
        $this->assertArrayHasKey('MULTISAFEPAY_OFFICIAL_TEST_API_KEY', $configFields);
        $this->assertArrayHasKey('MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_VALUE', $configFields);
        $this->assertArrayHasKey('MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_UNIT', $configFields);
        $this->assertArrayHasKey('MULTISAFEPAY_OFFICIAL_DEBUG_MODE', $configFields);

        // Test default values for some fields
        $this->assertEquals('0', $configFields['MULTISAFEPAY_OFFICIAL_TEST_MODE']['default']);
        $this->assertEquals('', $configFields['MULTISAFEPAY_OFFICIAL_API_KEY']['default']);
        $this->assertEquals('30', $configFields['MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_VALUE']['default']);
        $this->assertEquals(SettingsBuilder::DAYS, $configFields['MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_UNIT']['default']);
        $this->assertEquals('0', $configFields['MULTISAFEPAY_OFFICIAL_DEBUG_MODE']['default']);
        $this->assertEquals('1', $configFields['MULTISAFEPAY_OFFICIAL_SECOND_CHANCE']['default']);
    }

    /**
     * Test that each config field has the required structure
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder::getConfigFieldsAndDefaultValues
     */
    public function testConfigFieldsStructure(): void
    {
        $configFields = SettingsBuilder::getConfigFieldsAndDefaultValues();

        foreach ($configFields as $fieldName => $fieldConfig) {
            $this->assertIsArray($fieldConfig, "Field '$fieldName' should be an array");
            $this->assertArrayHasKey('default', $fieldConfig, "Field '$fieldName' should have a 'default' key");
            $this->assertIsString($fieldName, "Field name should be a string");

            // Check that field names start with the expected prefix
            $this->assertStringStartsWith('MULTISAFEPAY_OFFICIAL_', $fieldName);
        }
    }

    /**
     * Test that the constructor works correctly
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder::__construct
     */
    public function testConstructor(): void
    {
        $settingsBuilder = new SettingsBuilder($this->mockModule);
        $this->assertInstanceOf(SettingsBuilder::class, $settingsBuilder);
    }

    /**
     * Test specific config field values
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder::getConfigFieldsAndDefaultValues
     */
    public function testSpecificConfigValues(): void
    {
        $configFields = SettingsBuilder::getConfigFieldsAndDefaultValues();

        // Test order description default
        $this->assertEquals(
            'Payment for order: {order_reference}',
            $configFields['MULTISAFEPAY_OFFICIAL_ORDER_DESCRIPTION']['default']
        );

        // Test confirmation email default
        $this->assertEquals(
            '1',
            $configFields['MULTISAFEPAY_OFFICIAL_CONFIRMATION_ORDER_EMAIL']['default']
        );

        // Test create order before payment default
        $this->assertEquals(
            '1',
            $configFields['MULTISAFEPAY_OFFICIAL_CREATE_ORDER_BEFORE_PAYMENT']['default']
        );

        // Test disable shopping cart default
        $this->assertEquals(
            '0',
            $configFields['MULTISAFEPAY_OFFICIAL_DISABLE_SHOPPING_CART']['default']
        );
    }

    /**
     * Test that fields marked as multiple have the correct structure
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder::getConfigFieldsAndDefaultValues
     */
    public function testMultipleFieldsStructure(): void
    {
        $configFields = SettingsBuilder::getConfigFieldsAndDefaultValues();

        // Check fields that should have 'multiple' => true
        $multipleFields = array_filter($configFields, function ($field) {
            return isset($field['multiple']) && $field['multiple'] === true;
        });

        $this->assertNotEmpty($multipleFields);

        // Test that final order status is marked as multiple
        $this->assertTrue(
            isset($configFields['MULTISAFEPAY_OFFICIAL_FINAL_ORDER_STATUS']['multiple'])
        );
        $this->assertTrue(
            $configFields['MULTISAFEPAY_OFFICIAL_FINAL_ORDER_STATUS']['multiple']
        );
    }

    /**
     * Test that all required configuration fields are present
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder::getConfigFieldsAndDefaultValues
     */
    public function testRequiredFieldsPresent(): void
    {
        $configFields = SettingsBuilder::getConfigFieldsAndDefaultValues();

        $requiredFields = [
            'MULTISAFEPAY_OFFICIAL_TEST_MODE',
            'MULTISAFEPAY_OFFICIAL_API_KEY',
            'MULTISAFEPAY_OFFICIAL_TEST_API_KEY',
            'MULTISAFEPAY_OFFICIAL_DEBUG_MODE',
            'MULTISAFEPAY_OFFICIAL_ORDER_DESCRIPTION'
        ];

        foreach ($requiredFields as $requiredField) {
            $this->assertArrayHasKey($requiredField, $configFields);
        }
    }
}
