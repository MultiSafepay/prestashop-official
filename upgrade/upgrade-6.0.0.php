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

use MultiSafepay\PrestaShop\Services\PaymentOptionService;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @return bool
 */
function upgrade_module_6_0_0(): bool
{
    if (Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_CREDITCARD') === '1') {
        Configuration::updateValue('MULTISAFEPAY_OFFICIAL_GROUP_CREDITCARDS', '1');
    }

    $paymentOptionService = new PaymentOptionService(new MultisafepayOfficial());
    foreach ($paymentOptionService->getMultiSafepayPaymentOptions() as $paymentOption) {
        // Adding default values for countries of the branded payment methods
        $brandedCountries = $paymentOption->getAllowedCountries();
        if (!empty($brandedCountries)) {
            $isoBrandedCountries = [];
            foreach ($brandedCountries as $brandedCountry) {
                $isoBrandedCountries[] = (string)Country::getByIso($brandedCountry);
            }
            Configuration::updateGlobalValue('MULTISAFEPAY_OFFICIAL_COUNTRIES_' .
                $paymentOption->getUniqueName(), json_encode($isoBrandedCountries));
        }
    }

    return true;
}
