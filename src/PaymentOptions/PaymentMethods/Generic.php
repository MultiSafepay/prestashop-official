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
 */

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use Configuration;

class Generic extends BasePaymentOption
{

    public function getPaymentOptionName(): string
    {
        return 'Generic Gateway';
    }

    public function getPaymentOptionDescription(): string
    {
        return (Configuration::get('MULTISAFEPAY_GENERIC_DESCRIPTION') ? Configuration::get('MULTISAFEPAY_GENERIC_DESCRIPTION') : '');
    }

    public function getPaymentOptionGatewayCode(): string
    {
        return (Configuration::get('MULTISAFEPAY_GATEWAY_CODE_GENERIC') ? Configuration::get('MULTISAFEPAY_GATEWAY_CODE_GENERIC') : '');
    }

    public function getTransactionType(): string
    {
        return 'redirect';
    }

    public function getPaymentOptionLogo(): string
    {
        return (Configuration::get('MULTISAFEPAY_GATEWAY_IMAGE_GENERIC') ? Configuration::get('MULTISAFEPAY_GATEWAY_IMAGE_GENERIC') : '');
    }

    public function getUniqueName(): string
    {
        return 'GENERIC';
    }

    public function getGatewaySettings(): array
    {
        $options = parent::getGatewaySettings();

        $options['MULTISAFEPAY_GATEWAY_CODE_'.$this->getUniqueName()] = Configuration::get('MULTISAFEPAY_GATEWAY_CODE_'.$this->getUniqueName());
        $options['MULTISAFEPAY_GATEWAY_IMAGE_'.$this->getUniqueName()] = Configuration::get('MULTISAFEPAY_GATEWAY_IMAGE_'.$this->getUniqueName());

        return $options;
    }
}
