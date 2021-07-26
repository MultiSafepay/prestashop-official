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
use MultiSafepay\PrestaShop\Services\IssuerService;
use Tools;

class Ideal extends BasePaymentOption
{

    public function getPaymentOptionName(): string
    {
        return 'iDEAL';
    }

    public function getTransactionType(): string
    {
        $checkoutVars = Tools::getAllValues();
        return empty($checkoutVars['issuer_id']) ? 'redirect' : 'direct';
    }

    public function getPaymentOptionGatewayCode(): string
    {
        return 'IDEAL';
    }

    public function getPaymentOptionDescription(): string
    {
        return 'iDEAL Description';
    }

    public function getPaymentOptionLogo(): string
    {
        return 'ideal.png';
    }

    public function getPaymentOptionForm(): bool
    {
        return true;
    }

    public function getInputFields(): array
    {
        $parent_inputs        = parent::getInputFields();
        $payment_method_input = array(
            'select' => array(
                array(
                    'name'          => 'issuer_id',
                    'placeholder'   => 'Select bank',
                    'options'       => IssuerService::getIssuers($this->getPaymentOptionGatewayCode())
                ),
            ),
        );
        return array_merge($parent_inputs, $payment_method_input);
    }
}
