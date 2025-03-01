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

use Cart;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta;
use Address;

class In3 extends BasePaymentOption
{
    public const CLASS_NAME = 'In3';
    protected $gatewayCode = 'IN3';
    protected $logo = 'in3.png';
    protected $canProcessRefunds = false;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->module->l('iDEAL+in3: Betaal in 3 delen (0% rente)', self::CLASS_NAME);
    }

    public function getGatewaySettings(): array
    {
        $options = parent::getGatewaySettings();

        $options['MULTISAFEPAY_OFFICIAL_MIN_AMOUNT_'.$this->getUniqueName()]['default'] = '100';
        $options['MULTISAFEPAY_OFFICIAL_MAX_AMOUNT_'.$this->getUniqueName()]['default'] = '3000';

        return $options;
    }

    public function getGatewayInfo(Cart $cart, array $data = []): ?GatewayInfoInterface
    {
        $gatewayInfo = new Meta();
        $gatewayInfo->addPhoneAsString((new Address($cart->id_address_invoice))->phone);

        return $gatewayInfo;
    }

    public function getTransactionType(): string
    {
        return self::DIRECT_TYPE;
    }
}
