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

use Address;
use Cart;
use Context;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use Tools;

class Zinia extends BasePaymentOption
{
    public const CLASS_NAME = 'Zinia';
    protected $gatewayCode = 'ZINIA';
    protected $logo = 'zinia.png';
    protected $hasConfigurableDirect = true;
    protected $hasConfigurablePaymentComponent = true;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->module->l('Zinia', self::CLASS_NAME);
    }

    /**
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function getTransactionType(): string
    {
        $checkoutVars = Tools::getAllValues();
        return (empty($checkoutVars['gender']) || empty($checkoutVars['birthday'])) ? self::REDIRECT_TYPE : self::DIRECT_TYPE;
    }

    /**
     * @return array[]
     */
    public function getDirectTransactionInputFields(): array
    {
        return [
            [
                'type'          => 'select',
                'name'          => 'gender',
                'placeholder'   => $this->module->l('Salutation', self::CLASS_NAME),
                'options'       => [
                    [
                        'value' => 'mr',
                        'name'  => $this->module->l('Mr.', self::CLASS_NAME),
                    ],
                    [
                        'value' => 'mrs',
                        'name'  => $this->module->l('Mrs.', self::CLASS_NAME),
                    ],
                ],
            ],
            [
                'type'          => 'date',
                'name'          => 'birthday',
                'placeholder'   => $this->module->l('Birthday', self::CLASS_NAME),
                'value'         => Context::getContext()->customer->birthday ?? '',
            ]
        ];
    }

    public function getGatewayInfo(Cart $cart, array $data = []): ?GatewayInfoInterface
    {
        if (empty($data['gender']) && empty($data['birthday'])) {
            return null;
        }

        $gatewayInfo = new Meta();
        $gatewayInfo->addPhoneAsString((new Address($cart->id_address_invoice))->phone);
        if (!empty($data['gender'])) {
            $gatewayInfo->addGenderAsString($data['gender']);
        }
        if (!empty($data['birthday'])) {
            $gatewayInfo->addBirthdayAsString($data['birthday']);
        }

        return $gatewayInfo;
    }
}
