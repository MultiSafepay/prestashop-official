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

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use Country;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;

class Zinia extends BasePaymentOption
{
    public function getGatewaySettings(): array
    {
        $options = parent::getGatewaySettings();

        $options['MULTISAFEPAY_OFFICIAL_MIN_AMOUNT_' . $this->gatewayCode]['default'] = '50';
        $options['MULTISAFEPAY_OFFICIAL_MAX_AMOUNT_' . $this->gatewayCode]['default'] = '750';
        $options['MULTISAFEPAY_OFFICIAL_COUNTRIES_' . $this->gatewayCode]['default'] = json_encode(
            [(string)Country::getByIso('NL')]
        ) ?: [];

        return $options;
    }
}
