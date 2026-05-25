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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Initialize the new default payment method setting and backfill order-state icons.
 *
 * Configuration initialization is blocking and fails the upgrade when the new
 * setting cannot be persisted. The icon copy step remains best-effort: it tries
 * module icon candidates first, falls back to img/os/2.gif, and returns success
 * when icon prerequisites are not available.
 *
 * @param mixed|null $module
 * @return bool
 */
function upgrade_module_6_4_0($module = null): bool
{
    if (!upgrade640EnsureDefaultPaymentMethodConfigExists()) {
        return false;
    }

    $destinationDirectory = upgrade640GetOrderStateIconsDirectory();
    if (!is_dir($destinationDirectory) || !is_writable($destinationDirectory)) {
        upgrade640LogIconDestinationDirectoryUnavailable($destinationDirectory);
        return true;
    }

    foreach (upgrade640GetMultiSafepayOrderStatuses() as $statusKey => $statusValues) {
        $orderStateId = (int)Configuration::get($statusKey);
        if ($orderStateId <= 0) {
            continue;
        }

        upgrade640CloneOrderStateIcon($orderStateId, $statusValues, $destinationDirectory);
    }

    return true;
}

/**
 * Ensure upgraded installations persist the new default payment method setting.
 *
 * @return bool
 */
function upgrade640EnsureDefaultPaymentMethodConfigExists(): bool
{
    $configKey = 'MULTISAFEPAY_OFFICIAL_DEFAULT_PAYMENT_METHOD';

    if (Configuration::get($configKey) !== false) {
        return true;
    }

    // Seed a deterministic global fallback for upgraded installs. Merchants can
    // still save a different value per shop or shop group later, because
    // PrestaShop resolves shop and group overrides before the global value.
    if (Configuration::updateGlobalValue($configKey, '')) {
        return true;
    }

    $message = sprintf(
        'MultiSafepay upgrade 6.3.1: failed to initialize configuration key "%s".',
        $configKey
    );

    if (class_exists('PrestaShopLogger')) {
        PrestaShopLogger::addLog($message, 3);
        return false;
    }

    error_log($message);

    return false;
}

/**
 * Log a warning when the destination directory is missing or not writable.
 *
 * @param string $destinationDirectory
 * @return void
 */
function upgrade640LogIconDestinationDirectoryUnavailable(string $destinationDirectory): void
{
    $message = sprintf(
        'MultiSafepay upgrade 6.3.1: destination order-state icon directory "%s" '
        . 'is missing or not writable. Skipping icon backfill.',
        $destinationDirectory
    );

    if (class_exists('PrestaShopLogger')) {
        PrestaShopLogger::addLog($message, 2);
        return;
    }

    error_log($message);
}

/**
 * Return configured status keys and icon candidates used by the installer.
 *
 * @return array<string, array{name:string,icon:string}>
 */
function upgrade640GetMultiSafepayOrderStatuses(): array
{
    return [
        'MULTISAFEPAY_OFFICIAL_OS_AUTHORIZED' => [
            'name' => 'authorized',
            'icon' => 'authorized.gif',
        ],
        'MULTISAFEPAY_OFFICIAL_OS_CHARGEBACK' => [
            'name' => 'chargeback',
            'icon' => 'chargeback.gif',
        ],
        'MULTISAFEPAY_OFFICIAL_OS_INITIALIZED' => [
            'name' => 'initialized',
            'icon' => 'initialized.gif',
        ],
        'MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED' => [
            'name' => 'partially captured',
            'icon' => 'partially_captured.gif',
        ],
        'MULTISAFEPAY_OFFICIAL_OS_PARTIAL_REFUNDED' => [
            'name' => 'partial refunded',
            'icon' => 'partial_refunded.gif',
        ],
        'MULTISAFEPAY_OFFICIAL_OS_UNCLEARED' => [
            'name' => 'uncleared',
            'icon' => 'uncleared.gif',
        ],
    ];
}

/**
 * Resolve the absolute path of the order-state icon directory.
 *
 * @return string
 */
function upgrade640GetOrderStateIconsDirectory(): string
{
    if (defined('_PS_ORDER_STATE_IMG_DIR_')) {
        return rtrim(_PS_ORDER_STATE_IMG_DIR_, '/\\')
            . DIRECTORY_SEPARATOR;
    }

    return rtrim(_PS_IMG_DIR_, '/\\')
        . DIRECTORY_SEPARATOR
        . 'os'
        . DIRECTORY_SEPARATOR;
}

/**
 * Clone an order-state icon for a given order-state ID when missing.
 *
 * This function is idempotent: if the destination icon already exists,
 * it performs no write operation.
 *
 * @param int $orderStateId
 * @param array $multisafepayOrderStatusValues
 * @param string $destinationDirectory
 * @return void
 */
function upgrade640CloneOrderStateIcon(
    int $orderStateId,
    array $multisafepayOrderStatusValues,
    string $destinationDirectory
): void {
    if ($orderStateId <= 0) {
        return;
    }

    $destinationPath = $destinationDirectory . $orderStateId . '.gif';
    if (is_file($destinationPath)) {
        return;
    }

    $sourceDirectory = _PS_MODULE_DIR_ . 'multisafepayofficial/views/img/order_states/';

    $candidates = [];

    if (!empty($multisafepayOrderStatusValues['icon'])) {
        $candidates[] = basename((string)$multisafepayOrderStatusValues['icon']);
    }

    if (!empty($multisafepayOrderStatusValues['name'])) {
        $slug = strtolower((string)$multisafepayOrderStatusValues['name']);
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
        $slug = trim((string)$slug, '_');
        if ($slug !== '') {
            $candidates[] = $slug . '.gif';
        }
    }

    $candidates[] = 'default.gif';
    $candidates = array_unique($candidates);

    $lastSourcePathTried = '';

    if (is_dir($sourceDirectory)) {
        foreach ($candidates as $candidate) {
            $candidate = (string)$candidate;
            if ($candidate === '') {
                continue;
            }

            if (pathinfo($candidate, PATHINFO_EXTENSION) === '') {
                $candidate .= '.gif';
            }

            $sourcePath = $sourceDirectory . $candidate;
            if (!is_file($sourcePath) || !is_readable($sourcePath)) {
                continue;
            }

            $lastSourcePathTried = $sourcePath;

            if (@copy($sourcePath, $destinationPath)) {
                return;
            }
        }
    }

    $fallbackSourcePath = $destinationDirectory . '2.gif';
    if (is_file($fallbackSourcePath) && is_readable($fallbackSourcePath)) {
        $lastSourcePathTried = $fallbackSourcePath;
        if (@copy($fallbackSourcePath, $destinationPath)) {
            return;
        }
    }

    if ($lastSourcePathTried !== '') {
        upgrade640LogIconCopyFailure($lastSourcePathTried, $destinationPath);
        return;
    }

    upgrade640LogMissingIconSource(
        $destinationPath,
        $sourceDirectory,
        $fallbackSourcePath,
        $candidates
    );
}

/**
 * Log a warning when an icon copy operation fails during upgrade.
 *
 * @param string $sourcePath
 * @param string $destinationPath
 * @return void
 */
function upgrade640LogIconCopyFailure(string $sourcePath, string $destinationPath): void
{
    $message = sprintf(
        'MultiSafepay upgrade 6.3.1: failed to copy order-state icon from "%s" to "%s".',
        $sourcePath,
        $destinationPath
    );

    if (class_exists('PrestaShopLogger')) {
        PrestaShopLogger::addLog($message, 2);
        return;
    }

    error_log($message);
}

/**
 * Log a warning when no readable icon source can be found during upgrade.
 *
 * @param string $destinationPath
 * @param string $sourceDirectory
 * @param string $fallbackSourcePath
 * @param array $candidates
 * @return void
 */
function upgrade640LogMissingIconSource(
    string $destinationPath,
    string $sourceDirectory,
    string $fallbackSourcePath,
    array $candidates
): void {
    $candidateNames = [];
    foreach ($candidates as $candidate) {
        $candidate = (string)$candidate;
        if ($candidate !== '') {
            $candidateNames[] = $candidate;
        }
    }

    $message = sprintf(
        'MultiSafepay upgrade 6.3.1: no readable order-state icon source found for "%s". '
        . 'Checked module source "%s" with candidates [%s] and fallback "%s".',
        $destinationPath,
        $sourceDirectory,
        !empty($candidateNames) ? implode(', ', $candidateNames) : '(none)',
        $fallbackSourcePath
    );

    if (class_exists('PrestaShopLogger')) {
        PrestaShopLogger::addLog($message, 2);
        return;
    }

    error_log($message);
}
