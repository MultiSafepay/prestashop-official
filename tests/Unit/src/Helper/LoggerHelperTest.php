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

use Exception;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\Tests\BaseMultiSafepayTest;

class LoggerHelperTest extends BaseMultiSafepayTest
{
    private $testLogFile;

    public function setUp(): void
    {
        parent::setUp();

        // Create a temporary log file for testing
        $this->testLogFile = sys_get_temp_dir() . '/test_multisafepay.log';

        // Clean up any existing test log file
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
    }

    public function tearDown(): void
    {
        // Clean up test log file
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }

        parent::tearDown();
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\LoggerHelper::getDefaultLogPath
     */
    public function testGetDefaultLogPath(): void
    {
        $logPath = LoggerHelper::getDefaultLogPath();

        $this->assertIsString($logPath);
        $this->assertStringContainsString('multisafepayofficial', $logPath);
        $this->assertStringEndsWith('.log', $logPath);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\LoggerHelper::log
     */
    public function testLogWithBasicMessage(): void
    {
        // Test that the method can be called without throwing exceptions
        LoggerHelper::log('info', 'Test message');

        // The assertion is that no exception was thrown
        $this->assertTrue(true);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\LoggerHelper::log
     */
    public function testLogWithOrderAndCartId(): void
    {
        // Test that the method can be called with order and cart IDs
        LoggerHelper::log('warning', 'Test warning message', false, '123', 456);

        // The assertion is that no exception was thrown
        $this->assertTrue(true);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\LoggerHelper::logException
     */
    public function testLogException(): void
    {
        $exception = new Exception('Test exception message');

        // Test that the method can be called without throwing exceptions
        LoggerHelper::logException('error', $exception, 'Custom error message');

        // The assertion is that no exception was thrown
        $this->assertTrue(true);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\LoggerHelper::logException
     */
    public function testLogExceptionWithOrderAndCartId(): void
    {
        $exception = new Exception('Test exception with IDs');

        // Test that the method can be called with order and cart IDs
        LoggerHelper::logException('critical', $exception, 'Critical error', '789', 101112);

        // The assertion is that no exception was thrown
        $this->assertTrue(true);
    }

    /**
     * Test different log levels
     *
     * @dataProvider logLevelProvider
     * @covers \MultiSafepay\PrestaShop\Helper\LoggerHelper::log
     */
    public function testDifferentLogLevels(string $level): void
    {
        LoggerHelper::log($level, "Test message for $level level");

        // The assertion is that no exception was thrown for any log level
        $this->assertTrue(true);
    }

    public function logLevelProvider(): array
    {
        return [
            ['emergency'],
            ['alert'],
            ['critical'],
            ['error'],
            ['warning'],
            ['notice'],
            ['info'],
            ['debug'],
        ];
    }

    /**
     * Test logging with an empty message
     *
     * @covers \MultiSafepay\PrestaShop\Helper\LoggerHelper::log
     */
    public function testLogWithEmptyMessage(): void
    {
        LoggerHelper::log('info');

        // The assertion is that no exception was thrown
        $this->assertTrue(true);
    }

    /**
     * Test logging with shouldCheckConfig flag
     *
     * @covers \MultiSafepay\PrestaShop\Helper\LoggerHelper::log
     */
    public function testLogWithConfigCheck(): void
    {
        LoggerHelper::log('debug', 'Debug message with config check', true);

        // The assertion is that no exception was thrown
        $this->assertTrue(true);
    }

    /**
     * Test that constants are properly defined
     */
    public function testConstants(): void
    {
        $this->assertIsString(LoggerHelper::LOG_DIRECTORY);
        $this->assertIsString(LoggerHelper::LOG_NAME);
        $this->assertIsInt(LoggerHelper::DEFAULT_CHMOD);
        $this->assertIsInt(LoggerHelper::MAX_LOG_FILES);

        $this->assertStringContainsString('logs', LoggerHelper::LOG_DIRECTORY);
        $this->assertEquals('multisafepayofficial.log', LoggerHelper::LOG_NAME);
        $this->assertEquals(0755, LoggerHelper::DEFAULT_CHMOD);
        $this->assertEquals(7, LoggerHelper::MAX_LOG_FILES);
    }

    /**
     * Test log file path construction
     *
     * @covers \MultiSafepay\PrestaShop\Helper\LoggerHelper::getDefaultLogPath
     */
    public function testLogFilePathConstruction(): void
    {
        $actualPath = LoggerHelper::getDefaultLogPath();

        // Test that the path contains the expected directory and has .log extension
        $this->assertStringContainsString(LoggerHelper::LOG_DIRECTORY, $actualPath);
        $this->assertStringEndsWith('.log', $actualPath);
        $this->assertStringContainsString('multisafepayofficial', $actualPath);
    }
}
