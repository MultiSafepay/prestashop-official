<?php declare(strict_types=1);
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\PrestaShop\Helper;

class LoggerHelper
{
    const MULTISAFEPAY_LOG_DESTINATION  = _PS_MODULE_DIR_ . 'multisafepay/logs/multisafepay_log';

    const LEVEL_VALUE = [
        0 => 'EMERGENCY',
        1 => 'ALERT',
        2 => 'CRITICAL',
        3 => 'ERROR',
        4 => 'WARNING',
        5 => 'NOTICE',
        6 => 'INFO',
        7 => 'DEBUG'
    ];

    /**
     * Return a formatted message to be written in the log file.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc5424#page-11
     *
     * @param string $message
     * @param int $level
     * @return string
     */
    private static function formatMessage(string $message, int $level): string
    {
        return '*' . self::LEVEL_VALUE[$level] . '* ' . "\t" . _PS_VERSION_ . "\t" . date('Y/m/d - H:i:s') . ': ' . $message . PHP_EOL;
    }

    /**
     * Log an emergency level message.
     *
     * @param string message
     * @return bool
     */
    public static function logEmergency(string $message): bool
    {
        return (bool) file_put_contents(self::MULTISAFEPAY_LOG_DESTINATION, self::formatMessage($message, 0), FILE_APPEND);
    }

    /**
     * Log an alert level message.
     *
     * @param string message
     * @return bool
     */
    public static function logAlert(string $message): bool
    {
        return (bool) file_put_contents(self::MULTISAFEPAY_LOG_DESTINATION, self::formatMessage($message, 1), FILE_APPEND);
    }

    /**
     * Log a critical level message.
     *
     * @param string message
     * @return bool
     */
    public static function logCritical(string $message): bool
    {
        return (bool) file_put_contents(self::MULTISAFEPAY_LOG_DESTINATION, self::formatMessage($message, 2), FILE_APPEND);
    }

    /**
     * Log an error level message.
     *
     * @param string message
     * @return bool
     */
    public static function logError(string $message): bool
    {
        return (bool) file_put_contents(self::MULTISAFEPAY_LOG_DESTINATION, self::formatMessage($message, 3), FILE_APPEND);
    }

    /**
     * Log a warning level message.
     *
     * @param string message
     * @return bool
     */
    public static function logWarning(string $message): bool
    {
        return (bool) file_put_contents(self::MULTISAFEPAY_LOG_DESTINATION, self::formatMessage($message, 4), FILE_APPEND);
    }

    /**
     * Log a notice level message.
     *
     * @param string message
     * @return bool
     */
    public static function logNotice(string $message): bool
    {
        return (bool) file_put_contents(self::MULTISAFEPAY_LOG_DESTINATION, self::formatMessage($message, 5), FILE_APPEND);
    }

    /**
     * Log an info level message.
     *
     * @param string message
     * @return bool
     */
    public static function logInfo(string $message): bool
    {
        return (bool) file_put_contents(self::MULTISAFEPAY_LOG_DESTINATION, self::formatMessage($message, 6), FILE_APPEND);
    }

    /**
     * Log a debug level message.
     *
     * @param string message
     * @return bool
     */
    public static function logDebug(string $message): bool
    {
        return (bool) file_put_contents(self::MULTISAFEPAY_LOG_DESTINATION, self::formatMessage($message, 7), FILE_APPEND);
    }
}
