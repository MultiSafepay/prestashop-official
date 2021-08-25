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

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Services\IssuerService;
use Tools;
use PaymentModule;

class Ideal extends BasePaymentOption
{
    public $hasConfigurableDirect = true;

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

    public function getPaymentOptionLogo(): string
    {
        return 'ideal.png';
    }

    public function getPaymentOptionForm(): bool
    {
        return true;
    }

    public function getDirectTransactionInputFields(): array
    {
        /** @var IssuerService $issuerService */
        $issuerService        = $this->module->get('multisafepay.issuer_service');
        return [
            'select' => [
                [
                    'name'        => 'issuer_id',
                    'placeholder' => 'Select bank',
                    'options'     => $issuerService->getIssuers($this->getPaymentOptionGatewayCode()),
                ],
            ],
        ];
    }

    public function getGatewayInfo(array $data = []): GatewayInfoInterface
    {
        $gatewayInfo = new \MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Ideal();
        $gatewayInfo->addIssuerId($data['issuer_id']);
        return $gatewayInfo;
    }
}
