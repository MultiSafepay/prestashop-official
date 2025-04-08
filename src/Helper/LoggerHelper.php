<?php
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\PrestaShop\Helper;

use Configuration;
use Exception;
use Throwable;

class LoggerHelper
{
    /**
     * Log directory finished with a trailing slash
     *
     * @var string
     */
    public const LOG_DIRECTORY = _PS_ROOT_DIR_ . '/var/logs/';

    /**
     * Log file name
     *
     * @var string
     */
    public const LOG_NAME = 'multisafepayofficial.log';

    /**
     * Default log file permissions
     *
     * @var int
     */
    public const DEFAULT_CHMOD = 0755;

    /**
     * Maximum number of log files to keep
     *
     * @var int
     */
    public const MAX_LOG_FILES = 7;

    /**
     * Log levels mapping according to PSR-3
     */
    private const LOG_LEVELS = [
        'emergency' => 0,
        'alert'     => 1,
        'critical'  => 2,
        'error'     => 3,
        'warning'   => 4,
        'notice'    => 5,
        'info'      => 6,
        'debug'     => 7
    ];

    /**
     * Creates and configures a native PHP logger instance
     *
     * @param int $logLevel The log level to be used
     * @return array|false Returns an array with file handle and log level, or false on failure
     */
    private static function getLogger(int $logLevel)
    {
        $logPath = self::getDefaultLogPath();

        // Rotate logs if needed
        self::rotateLogFiles($logPath);

        // Open file in appended mode
        $fileHandle = fopen($logPath, 'ab');
        if ($fileHandle === false) {
            return false;
        }

        // Set file permissions if needed
        if (is_writable($logPath)) {
            chmod($logPath, self::DEFAULT_CHMOD);
        }

        return [
            'handle' => $fileHandle,
            'level' => $logLevel
        ];
    }

    /**
     * Rotates log files when needed, keeping only the specified number of backup files
     *
     * @param string $logPath Path to the main log file
     * @return void
     */
    private static function rotateLogFiles(string $logPath): void
    {
        // Check if the log file exists and exceeds 5MB
        if (!file_exists($logPath) || filesize($logPath) < 5 * 1024 * 1024) {
            return;
        }

        $baseDir = dirname($logPath);
        $baseFileName = pathinfo(self::LOG_NAME, PATHINFO_FILENAME);

        // Get all log files and sort them by date (newest first)
        $pattern = $baseDir . '/' . $baseFileName . '-*.log';
        $files = glob($pattern);
        usort($files, static function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Remove the oldest files if we exceed MAX_LOG_FILES
        while (count($files) >= self::MAX_LOG_FILES) {
            $oldestFile = array_pop($files);
            if (file_exists($oldestFile)) {
                unlink($oldestFile);
            }
        }

        // If today's log file already exists, append a number
        $today = date('Y-m-d');
        $newLogFile = $baseDir . '/' . $baseFileName . '-' . $today . '.log';
        $counter = 1;
        $originalNewLogFile = $newLogFile;
        while (file_exists($newLogFile) && $counter < 10) { // Limit to avoid any potential infinite loop
            $newLogFile = substr($originalNewLogFile, 0, -4) . '-' . $counter . '.log';
            $counter++;
        }

        // Rename the current log file to new name with date
        if (file_exists($logPath)) {
            rename($logPath, $newLogFile);
        }

        // Create a new log file with date
        $newLogPath = self::getDefaultLogPath();
        touch($newLogPath);
        chmod($newLogPath, self::DEFAULT_CHMOD);
    }

    /**
     * Writes a log entry with additional context information
     *
     * @param array $logger Array containing file handle and log level
     * @param string $level The log level name
     * @param string $message The message to log
     * @return void
     */
    private static function writeLogEntry(array $logger, string $level, string $message): void
    {
        if (!isset($logger['handle']) || !$logger['handle']) {
            return;
        }

        // Check if we should log this message based on level
        if (!self::shouldLog($logger['level'], self::mapLevel($level))) {
            fclose($logger['handle']);
            return;
        }

        $datetime = date('Y-m-d H:i:s');
        $hostname = gethostname() ?: 'unknown';
        $requestInfo = self::getRequestInfo();
        $debugInfo = self::getDebugInfo();

        $logEntry = sprintf(
            '[%s] %s: %s - Hostname: %s %s %s%s',
            $datetime,
            strtoupper($level),
            $message,
            $hostname,
            $requestInfo,
            $debugInfo,
            PHP_EOL
        );

        // Attempt to write to the file
        $writeResult = fwrite($logger['handle'], $logEntry);

        // Force writing to disk
        fflush($logger['handle']);

        // Verify if the writing was successful
        if (($writeResult === false) || ($writeResult !== strlen($logEntry))) {
            error_log('MultiSafepay: Failed to write to log file: ' . $logEntry);
        }

        fclose($logger['handle']);
    }

    /**
     * Determines if a message should be logged based on log levels
     *
     * @param int $configuredLevel The configured log level
     * @param int $messageLevel The message log level
     * @return bool Returns true if the message should be logged
     */
    private static function shouldLog(int $configuredLevel, int $messageLevel): bool
    {
        // Lower numbers are more severe in our LOG_LEVELS mapping,
        // So we log if the message level is less than or equal to the configured level
        return $messageLevel <= $configuredLevel;
    }

    /**
     * Gets information about the current request
     *
     * @return string
     */
    private static function getRequestInfo(): string
    {
        $info = [];
        if (!empty($_SERVER['REQUEST_URI'])) {
            $info[] = '- URI: ' . $_SERVER['REQUEST_URI'];
        }
        if (!empty($_SERVER['REQUEST_METHOD'])) {
            $info[] = '- Method: ' . $_SERVER['REQUEST_METHOD'];
        }
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $info[] = '- IP: ' . $_SERVER['REMOTE_ADDR'];
        }
        return implode(' ', $info);
    }

    /**
     * Gets debug information including a file, line and class
     *
     * @return string
     */
    private static function getDebugInfo(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        $caller = $trace[3] ?? $trace[0];

        return sprintf(
            '- File: %s - Line: %s - Class: %s',
            $caller['file'] ?? 'unknown',
            $caller['line'] ?? 'unknown',
            $caller['class'] ?? 'unknown'
        );
    }

    /**
     * Gets the default log path
     *
     * @return string The default log file path with the current date
     */
    public static function getDefaultLogPath(): string
    {
        // The default log directory
        $logDirectory = self::LOG_DIRECTORY;
        $today = date('Y-m-d');
        $baseFileName = pathinfo(self::LOG_NAME, PATHINFO_FILENAME);
        $logName = $baseFileName . '-' . $today . '.log';

        // Alternative log in the platform root directory
        $alternativeLogPath = _PS_ROOT_DIR_ . '/' . $logName;
        // Log in the system temporary directory
        $tempLogPath = sys_get_temp_dir() . '/' . $logName;

        // Check if the directory exists and attempt to create it, making another final check
        if (!is_dir($logDirectory) && !mkdir($logDirectory, self::DEFAULT_CHMOD, true) && !is_dir($logDirectory)) {
            if (is_writable(_PS_ROOT_DIR_)) {
                return $alternativeLogPath;
            }
            return $tempLogPath;
        }

        // Check if the directory is writable and attempt to set the permissions
        if (!is_writable($logDirectory) && !chmod($logDirectory, self::DEFAULT_CHMOD)) {
            if (is_writable(_PS_ROOT_DIR_)) {
                return $alternativeLogPath;
            }
            return $tempLogPath;
        }

        // Return the default log file path with date
        return $logDirectory . $logName;
    }

    /**
     * Check if the debug mode is enabled
     *
     * @return bool Returns true if debug mode is enabled, false otherwise
     */
    private static function checkConfigDebugMode(): bool
    {
        return Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE') === '1';
    }

    /**
     * Map the log level from string to integer
     *
     * @param string $level The log level as a string
     * @return int The corresponding log level as an integer
     */
    private static function mapLevel(string $level = 'info'): int
    {
        return self::LOG_LEVELS[strtolower($level)] ?? self::LOG_LEVELS['info'];
    }

    /**
     * Log a message at a given level
     */
    public static function log(
        string $level,
        string $message = '',
        bool $shouldCheckConfig = false,
        ?string $orderId = null,
        ?int $cartId = null
    ): void {
        if ($shouldCheckConfig && !self::checkConfigDebugMode()) {
            return;
        }

        try {
            $logger = self::getLogger(self::mapLevel($level));
            if ($logger) {
                $formattedMessage = self::formatMessage($orderId, (string)$cartId, $message);
                self::writeLogEntry($logger, $level, $formattedMessage);
            }
        } catch (Exception $exception) {
            return;
        }
    }

    /**
     * Log focused on Exceptions
     */
    public static function logException(
        string $level,
        Throwable $exception,
        string $message = '',
        ?string $orderId = null,
        ?int $cartId = null
    ): void {
        $message .= (!empty($message) ? ' - ' : '');
        $logLevel = self::mapLevel($level);
        $formattedMsg = sprintf(
            'Exception Info: %4$s %3$s [Code: %1$d. Line: %2$d]',
            $exception->getCode(),
            $exception->getLine(),
            (!empty($exception->getMessage()) ? 'Message from Class: ' . $exception->getMessage() : ''),
            (!empty($message) ? 'Custom Message: ' . $message : '')
        );
        $formattedMsg = preg_replace('/\s+/', ' ', $formattedMsg);

        try {
            $logger = self::getLogger($logLevel);
            if ($logger) {
                $formattedMessage = self::formatMessage($orderId, (string)$cartId, $formattedMsg);
                self::writeLogEntry($logger, $level, $formattedMessage);
            }
        } catch (Exception $exception) {
            return;
        }
    }

    /**
     * Return a formatted message to be written in the log file
     *
     * @see https://datatracker.ietf.org/doc/html/rfc5424#page-11
     *
     * @param ?string $orderId Order ID. Can be null
     * @param ?string $cartId Cart ID. Can be null
     * @param string $message Message to be written in the log
     * @return string Returns the formatted message
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    private static function formatMessage(
        ?string $orderId = null,
        ?string $cartId = null,
        string $message = ''
    ): string {
        $orderString = 'Order ID: ' . (!empty($orderId) ? $orderId : '--') . ' - ';
        $cartString = 'Cart ID: ' . (!empty($cartId) ? $cartId : '--');
        return $orderString . $cartString  . "\tVersion: " . _PS_VERSION_ . "\t" . $message;
    }
}
