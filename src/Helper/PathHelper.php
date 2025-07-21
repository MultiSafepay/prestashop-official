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

use InvalidArgumentException;

/**
 * Class PathHelper
 *
 * Static helper to build asset paths for CSS and JS files
 *
 * Automatically detects a file type by extension and builds complete paths
 */
class PathHelper
{
    /**
     * @var string|null Relative module path from $this->_path
     */
    private static $modulePath = null;

    /**
     * Initialize with the relative module path
     *
     * @param string $path Relative path
     * @return void
     */
    public static function initialize(string $path): void
    {
        self::$modulePath = $path;
    }

    /**
     * Check if PathHelper is properly initialized
     *
     * @return bool
     */
    public static function isInitialized(): bool
    {
        return self::$modulePath !== null;
    }

    /**
     * Generic method to build asset paths
     *
     * @param string $fileName File name with extension (.css or .js)
     * @return string Complete asset path
     */
    public static function getAssetPath(string $fileName): string
    {
        $extension = strtolower(substr($fileName, strrpos($fileName, '.') + 1));

        if (!in_array($extension, ['css', 'js'])) {
            throw new InvalidArgumentException(
                'Unsupported file extension: ' . $extension .
                'Only .css and .js files are supported.'
            );
        }

        $viewsPath = 'views/' . $extension . '/' . $fileName;
        $modulePath = self::$modulePath ?? '/modules/multisafepayofficial/';

        // Ensure proper path concatenation
        return rtrim($modulePath, '/') . '/' . $viewsPath;
    }
}
