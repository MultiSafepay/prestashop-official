<?php
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
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

namespace MultiSafepay\PrestaShop\Util;

use Language;
use Tools;

/**
 * Class LanguageUtil
 * @package MultiSafepay\PrestaShop\Util
 */
class LanguageUtil
{
    /**
     * Return locale code
     *
     * @param int $languageId
     *
     * @return string
     * @throws \PrestaShopException
     */
    public function getLanguageCode(int $languageId): string
    {
        $isoCode = Language::getIsoById($languageId);
        $locale = Language::getLanguageCodeByIso($isoCode);

        if (Tools::strlen($locale) === 2) {
            return Tools::strtolower($locale) . '_' . Tools::strtoupper($locale);
        }

        $parts = explode('-', (string) $locale);

        return Tools::strtolower($parts[0]).'_'.Tools::strtoupper($parts[1]);
    }
}
