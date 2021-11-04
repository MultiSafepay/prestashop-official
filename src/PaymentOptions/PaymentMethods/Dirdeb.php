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
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Account;
use MultiSafepay\ValueObject\IbanNumber;
use MultiSafepay\Exception\InvalidArgumentException;
use Tools;
use Order;

class Dirdeb extends BasePaymentOption
{
    protected $hasConfigurableDirect = true;
    protected $hasConfigurableTokenization = true;
    protected $name = 'SEPA Direct Debit';
    protected $gatewayCode = 'DIRDEB';
    protected $logo = 'dirdeb.png';

    /**
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function getTransactionType(): string
    {
        $checkoutVars = Tools::getAllValues();
        return (empty($checkoutVars['bankaccount']) || empty($checkoutVars['account_holder_name'])) ? self::REDIRECT_TYPE : self::DIRECT_TYPE;
    }

    public function getDirectTransactionInputFields(): array
    {
        return [
            [
                'type'          => 'text',
                'name'          => 'account_holder_name',
                'placeholder'   => $this->module->l('Account Holder Name'),
                'value'         => '',
            ],
            [
                'type'          => 'text',
                'name'          => 'bankaccount',
                'placeholder'   => $this->module->l('Bank Account'),
                'value'         => '',
            ],
            [
                'type'          => 'hidden',
                'name'          => 'emandate',
                'placeholder'   => '',
                'value'         => '1',
            ]
        ];
    }

    /**
     * @param Order $order
     * @param array $data
     * @return GatewayInfoInterface|null
     *
     * @phpcs:disable -- Disable to avoid trigger a warning in validator about unused parameter
     */
    public function getGatewayInfo(Order $order, array $data = []): ?GatewayInfoInterface
    {
        if (empty($data['bankaccount']) && empty($data['account_holder_name'])) {
            return null;
        }

        try {
            $ibanNumber = new IbanNumber($data['bankaccount']);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return null;
        }

        $gatewayInfo = new Account();
        $gatewayInfo->addAccountId($ibanNumber);
        $gatewayInfo->addAccountHolderIban($ibanNumber);
        $gatewayInfo->addAccountHolderName($data['account_holder_name']);
        return $gatewayInfo;
        // phpcs:enable
    }
}
