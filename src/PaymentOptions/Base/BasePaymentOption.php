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

use Configuration;
use Multisafepay;
use Context as PrestaShopContext;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use PaymentModule;

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

    /**
     * @var Multisafepay
     */
    public $module;

    /**
     * @var int
     */
    public $sortOrderPosition;

    public function __construct(Multisafepay $module)
    {
        $this->module           = $module;
        $this->name             = $this->getPaymentOptionName();
        $this->description      = $this->getPaymentOptionDescription();
        $this->gatewayCode      = $this->getPaymentOptionGatewayCode();
        $this->type             = $this->getTransactionType();
        $this->inputs           = $this->getInputFields();
        $this->callToActionText = $this->getFrontEndPaymentOptionName();
        $this->icon             = $this->getPaymentOptionLogo();
        $this->paymentForm      = $this->getPaymentOptionForm();
        $this->action           = PrestaShopContext::getContext()->link->getModuleLink('multisafepay', 'payment', array(), true);
        $this->sortOrderPosition = (int) Configuration::get('MULTISAFEPAY_SORT_ORDER_'.$this->getUniqueName());
    }

    public function getFrontEndPaymentOptionName(): string
    {
        return (Configuration::get('MULTISAFEPAY_TITLE_'.$this->getUniqueName()) ? Configuration::get('MULTISAFEPAY_TITLE_'.$this->getUniqueName()) : $this->getPaymentOptionName());
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
        return (Configuration::get('MULTISAFEPAY_DESCRIPTION_'.$this->getUniqueName()) ? Configuration::get('MULTISAFEPAY_DESCRIPTION_'.$this->getUniqueName()) : '');
    }

    public function getInputFields(): array
    {
        return [
            'hidden' => [
                [
                    'name'  => 'gateway',
                    'value' => $this->getPaymentOptionGatewayCode(),
                ],
                [
                    'name'  => 'type',
                    'value' => $this->getTransactionType(),
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    public function getUniqueName(): string
    {
        return $this->getPaymentOptionGatewayCode();
    }

    /**
     * @return array
     */
    public function getGatewaySettings(): array
    {
        return [
            'MULTISAFEPAY_GATEWAY_'.$this->getUniqueName() => Configuration::get('MULTISAFEPAY_GATEWAY_'.$this->getUniqueName()),
            'MULTISAFEPAY_TITLE_'.$this->getUniqueName() => Configuration::get('MULTISAFEPAY_TITLE_'.$this->getUniqueName()),
            'MULTISAFEPAY_DESCRIPTION_'.$this->getUniqueName() => Configuration::get('MULTISAFEPAY_DESCRIPTION_'.$this->getUniqueName()),
            'MULTISAFEPAY_MAX_AMOUNT_'.$this->getUniqueName() => Configuration::get('MULTISAFEPAY_MAX_AMOUNT_'.$this->getUniqueName()),
            'MULTISAFEPAY_MIN_AMOUNT_'.$this->getUniqueName() => Configuration::get('MULTISAFEPAY_MIN_AMOUNT_'.$this->getUniqueName()),
            'MULTISAFEPAY_COUNTRIES_'.$this->getUniqueName() => $this->settingToArray(Configuration::get('MULTISAFEPAY_COUNTRIES_'.$this->getUniqueName())),
            'MULTISAFEPAY_CURRENCIES_'.$this->getUniqueName() => $this->settingToArray(Configuration::get('MULTISAFEPAY_CURRENCIES_'.$this->getUniqueName())),
            'MULTISAFEPAY_CUSTOMER_GROUPS_'.$this->getUniqueName() => $this->settingToArray(Configuration::get('MULTISAFEPAY_CUSTOMER_GROUPS_'.$this->getUniqueName())),
            'MULTISAFEPAY_SORT_ORDER_'.$this->getUniqueName() => Configuration::get('MULTISAFEPAY_SORT_ORDER_'.$this->getUniqueName()),
        ];
    }

    /**
     * @param string $setting
     *
     * @return array
     */
    protected function settingToArray($setting): array
    {
        if (is_string($setting) && !empty($setting)) {
            return json_decode($setting);
        }
        return [];
    }

    /**
     * @param array $data
     *
     * @return GatewayInfoInterface
     */
    public function getGatewayInfo(array $data = []): GatewayInfoInterface
    {
        return new BaseGatewayInfo();
    }
}
