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

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BaseGatewayInfo;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta;
use Order;
use Address;
use Context;
use PaymentModule;

class Santander extends BasePaymentOption
{
    public const CLASS_NAME = 'Santander';
    protected $gatewayCode = 'SANTANDER';
    protected $logo = 'betaalplan.png';

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->module->l('Santander Consumer Finance | Pay per month', self::CLASS_NAME);
    }

    public function isDirect(): bool
    {
        return true;
    }

    public function getGatewaySettings(): array
    {
        $options = parent::getGatewaySettings();

        $options['MULTISAFEPAY_OFFICIAL_MIN_AMOUNT_'.$this->getUniqueName()]['default'] = '250';
        $options['MULTISAFEPAY_OFFICIAL_MAX_AMOUNT_'.$this->getUniqueName()]['default'] = '8000';

        return $options;
    }

    public function getDirectTransactionInputFields(): array
    {
        return [
            [
                'type'          => 'select',
                'name'          => 'gender',
                'placeholder'   => $this->module->l('Salutation', self::CLASS_NAME),
                'options'       => [
                    [
                        'value' => 'male',
                        'name'  => $this->module->l('Mr.', self::CLASS_NAME),
                    ],
                    [
                        'value' => 'female',
                        'name'  => $this->module->l('Mrs.', self::CLASS_NAME),
                    ],
                ],
            ],
            [
                'type'          => 'date',
                'name'          => 'birthday',
                'placeholder'   => $this->module->l('Birthday', self::CLASS_NAME),
                'value'         => Context::getContext()->customer->birthday ?? '',
            ],
            [
                'type'          => 'text',
                'name'          => 'bankaccount',
                'placeholder'   => $this->module->l('Bank Account', self::CLASS_NAME),
                'value'         => ''
            ],
        ];
    }
}
