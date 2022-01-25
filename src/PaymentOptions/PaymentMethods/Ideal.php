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
use MultiSafepay\PrestaShop\Services\IssuerService;
use Tools;
use Order;

class Ideal extends BasePaymentOption
{
    public const CLASS_NAME = 'Ideal';
    protected $gatewayCode = 'IDEAL';
    protected $logo = 'ideal.png';
    protected $hasConfigurableDirect = true;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->module->l('iDEAL', self::CLASS_NAME);
    }

    public function getTransactionType(): string
    {
        $checkoutVars = Tools::getAllValues();
        return empty($checkoutVars['issuer_id']) ? self::REDIRECT_TYPE : self::DIRECT_TYPE;
    }

    public function getDirectTransactionInputFields(): array
    {
        /** @var IssuerService $issuerService */
        $issuerService        = $this->module->get('multisafepay.issuer_service');
        return [
            [
                'type'        => 'select',
                'name'        => 'issuer_id',
                'placeholder' => $this->module->l('Select bank', self::CLASS_NAME),
                'options'     => $issuerService->getIssuers($this->getGatewayCode()),
            ],
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
        if (!isset($data['issuer_id'])) {
            return null;
        }
        $gatewayInfo = new \MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Ideal();
        $gatewayInfo->addIssuerId($data['issuer_id']);
        return $gatewayInfo;
        // phpcs:enable
    }
}
