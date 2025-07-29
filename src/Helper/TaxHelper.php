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
 * Class TaxHelper
 * @package MultiSafepay\PrestaShop\Helper
 */
class TaxHelper
{
    public const GATEWAY_CODE_BILLINK = 'BILLINK';

    /**
     * Round tax rate for BILLINK gateway to prevent rounding issues
     *
     * @param float $taxRate
     * @return float
     */
    public static function roundTaxRateForBillink(float $taxRate): float
    {
        if ($taxRate === 0.00) {
            return 0.00;
        }

        $allowedRates = [0, 5, 6, 7, 9, 16, 19, 20, 21];

        foreach ($allowedRates as $rate) {
            if (abs($taxRate - $rate) <= 0.05) {
                return round($taxRate);
            }
        }

        return $taxRate;
    }
}
