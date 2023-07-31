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
 * THE SOFTWARE IS PROVIDED "AS IS" WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use Cart;
use Context;
use Exception;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Issuer;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Services\IssuerService;
use Tools;

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

    /**
     * @return string
     */
    public function getTransactionType(): string
    {
        $checkoutVars = Tools::getAllValues();
        return empty($checkoutVars['issuer_id']) ? self::REDIRECT_TYPE : self::DIRECT_TYPE;
    }

    /**
     * @return array[]
     * @throws Exception
     */
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
                'class'       => 'select2-ideal'
            ],
        ];
    }

    /**
     * @param Cart $cart
     * @param array $data
     * @return GatewayInfoInterface|null
     *
     * @phpcs:disable -- Disable to avoid triggering a warning in validator about unused parameter
     */
    public function getGatewayInfo(Cart $cart, array $data = []): ?GatewayInfoInterface
    {
        if (!isset($data['issuer_id'])) {
            return null;
        }
        $gatewayInfo = new Issuer();
        $gatewayInfo->addIssuerId($data['issuer_id']);
        return $gatewayInfo;
        // phpcs:enable
    }

    /**
     * @param Context $context
     * @return void
     */
    public function registerJavascript(Context $context): void
    {
        $context->controller->registerJavascript(
            'module-multisafepay-select2',
            'modules/multisafepayofficial/views/js/select2.min.js'
        );

        $context->controller->registerJavascript(
            'module-multisafepay-ideal-javascript',
            'modules/multisafepayofficial/views/js/multisafepay-ideal.js'
        );

        parent::registerJavascript($context);
    }

    /**
     * @param Context $context
     * @return void
     */
    public function registerCss(Context $context): void
    {
        $context->controller->registerStylesheet(
            'module-multisafepay-select2-styles',
            'modules/multisafepayofficial/views/css/select2.min.css',
            [
                'priority' => 1
            ]
        );

        parent::registerCss($context);
    }
}
