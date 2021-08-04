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
 *
 */

namespace MultiSafepay\PrestaShop\PaymentOptions\Base;

use Context as PrestaShopContext;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;

abstract class BasePaymentOption implements BasePaymentOptionInterface
{

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $gatewayCode;

    /**
     * @var string
     */
    public $type;

    /**
     * @var array
     */
    public $inputs;

    /**
     * @var string
     */
    public $callToActionText;

    /**
     * @var string
     */
    public $icon;

    /**
     * @var boolean
     */
    public $paymentForm;

    /**
     * @var string
     */
    public $action;

    public function __construct()
    {
        $this->name                 = $this->getPaymentOptionName();
        $this->description          = $this->getPaymentOptionDescription();
        $this->gatewayCode          = $this->getPaymentOptionGatewayCode();
        $this->type                 = $this->getTransactionType();
        $this->inputs               = $this->getInputFields();
        $this->callToActionText     = $this->getPaymentOptionName();
        $this->icon                 = $this->getPaymentOptionLogo();
        $this->paymentForm          = $this->getPaymentOptionForm();
        $this->action               = PrestaShopContext::getContext()->link->getModuleLink('multisafepay', 'payment', array(), true);
    }

    public function getPaymentOptionLogo(): string
    {
        return '';
    }

    public function getTransactionType(): string
    {
        return 'redirect';
    }

    public function getPaymentOptionForm(): bool
    {
        return false;
    }

    public function getPaymentOptionDescription(): string
    {
        return '';
    }

    public function getInputFields(): array
    {
        return array(
            'hidden' => array(
                array(
                    'name'  => 'gateway',
                    'value' => $this->getPaymentOptionGatewayCode(),
                ),
                array(
                    'name'  => 'type',
                    'value' => $this->getTransactionType(),
                )
            )
        );
    }

    public function getGatewayInfo(array $data = []): GatewayInfoInterface
    {
        return new BaseGatewayInfo();
    }
}
