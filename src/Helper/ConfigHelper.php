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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class ConfigHelper
 *
 * Helper class for configuration parsing utilities
 *
 * @package MultiSafepay\PrestaShop\Helper
 */
class ConfigHelper
{
    /**
     * Convert a JSON-encoded setting string to an array of integers
     *
     * This method safely handles various edge cases:
     * - Empty strings return empty array
     * - Invalid JSON returns empty array (prevents Fatal Error in PHP 8+)
     * - Valid JSON array is converted to integers
     * - String numbers in JSON are converted to integers
     *
     * @param string $setting JSON-encoded array string (e.g., "[5,2,3,4]")
     * @return array Array of integers, or empty array if invalid
     */
    public static function settingToIntArray(string $setting): array
    {
        if (strlen($setting) > 0) {
            $decoded = json_decode($setting);
            // Ensure json_decode returned a valid array before using array_map
            // This prevents Fatal Error in PHP 8+ when json_decode returns null
            if (is_array($decoded)) {
                return array_map('intval', $decoded);
            }
        }

        return [];
    }
}
