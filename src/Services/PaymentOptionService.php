<?php
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

namespace MultiSafepay\PrestaShop\Services;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay;
use Multisafepay as MultiSafepayModule;
use Cart;
use Address;
use Customer;
use Media;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Symfony\Component\Finder\Finder;
use PaymentModule; // This line is here to prevent this PHPStan error: Internal error: Class 'PaymentModuleCore' not found

/**
 * Class PaymentOptionService
 * @package MultiSafepay\PrestaShop\Services
 */
class PaymentOptionService
{
    public const PAYMENT_OPTIONS_NAMESPACE = "MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\\";
    public const PAYMENT_OPTIONS_DIR = _PS_ROOT_DIR_.'/modules/multisafepay/src/PaymentOptions/PaymentMethods';

    /**
     * @var MultiSafepayModule
     */
    private $module;

    /**
     * SdkService constructor.
     */
    public function __construct(MultiSafepayModule $module)
    {
        $this->module = $module;
    }

    /**
     * Get all MultiSafepay payment options
     *
     * @return array
     */
    public function getMultiSafepayPaymentOptions(): array
    {
        $paymentOptions = [];
        foreach ($this->getPaymentOptionClassNamesFromDirectory() as $className) {
            $paymentOptions[] = new $className($this->module);
        }
        usort($paymentOptions, function ($a, $b) {
            return strcmp($a->sortOrderPosition, $b->sortOrderPosition);
        });
        return $paymentOptions;
    }

    /**
     * @param string $gatewayCode
     *
     * @return BasePaymentOption
     */
    public function getMultiSafepayPaymentOption(string $gatewayCode): BasePaymentOption
    {
        foreach ($this->getPaymentOptionClassNamesFromDirectory() as $className) {
            $paymentOption = new $className($this->module);
            if ($paymentOption->getPaymentOptionGatewayCode() === $gatewayCode) {
                return $paymentOption;
            }
        }
        return new MultiSafepay($this->module);
    }

    /**
     * Return  an array of MultiSafepay PaymentOptions
     *
     * @return array
     */
    public function getFilteredMultiSafepayPaymentOptions(Cart $cart): array
    {
        $paymentOptions = [];
        $paymentMethods = $this->getMultiSafepayPaymentOptions();
        foreach ($paymentMethods as $paymentMethod) {
            if ($this->excludePaymentOptionByPaymentOptionSettings($paymentMethod, $cart)) {
                continue;
            }
            $option = new PaymentOption();
            $option->setCallToActionText($paymentMethod->callToActionText);
            $option->setAction($paymentMethod->action);
            $option->setForm($this->module->getMultiSafepayPaymentOptionForm($paymentMethod->gatewayCode, $paymentMethod->inputs));
            if ($paymentMethod->icon && file_exists(_PS_MODULE_DIR_ . $this->module->name . '/views/img/' . $paymentMethod->icon)) {
                $option->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->module->name . '/views/img/' . $paymentMethod->icon));
            }
            if ($paymentMethod->description) {
                $option->setAdditionalInformation($paymentMethod->description);
            }
            $paymentOptions[] = $option;
        }
        return $paymentOptions;
    }

    /**
     * Filter the payment option according with their settings and the cart properties
     *
     * @param BasePaymentOption $paymentMethod
     * @param Cart $cart
     * @return bool
     */
    private function excludePaymentOptionByPaymentOptionSettings(BasePaymentOption $paymentMethod, Cart $cart)
    {
        $orderTotal             = $cart->getOrderTotal();
        $orderCountryId         = (new Address($cart->id_address_invoice))->id_country;
        $orderCurrencyId        = $cart->id_currency;
        $orderCustomerGroups    = (new Customer($cart->id_customer))->id_default_group;
        $orderCarrierId         = $cart->id_carrier;
        $isVirtual              = $cart->isVirtualCart();
        $isCartSplitted         = ($cart->getNbOfPackages() > 1) ? true : false;

        $paymentMethodSettings = $paymentMethod->getGatewaySettings();

        $paymentMethodStatus         = (bool) $paymentMethodSettings['MULTISAFEPAY_GATEWAY_' . $paymentMethod->getUniqueName()]['value'];
        $paymentMethodMinAmount      = (float) $paymentMethodSettings['MULTISAFEPAY_MIN_AMOUNT_' . $paymentMethod->getUniqueName()]['value'];
        $paymentMethodMaxAmount      = (float) $paymentMethodSettings['MULTISAFEPAY_MAX_AMOUNT_' . $paymentMethod->getUniqueName()]['value'];
        $paymentMethodCountries      = $paymentMethodSettings['MULTISAFEPAY_COUNTRIES_' . $paymentMethod->getUniqueName()]['value'];
        $paymentMethodCurrencies     = $paymentMethodSettings['MULTISAFEPAY_CURRENCIES_' . $paymentMethod->getUniqueName()]['value'];
        $paymentMethodCustomerGroups = $paymentMethodSettings['MULTISAFEPAY_CUSTOMER_GROUPS_' . $paymentMethod->getUniqueName()]['value'];
        $paymentMethodCarriers       = $paymentMethodSettings['MULTISAFEPAY_CARRIERS_' . $paymentMethod->getUniqueName()]['value'];

        if (!$paymentMethodStatus) {
            return true;
        }

        if (!empty($paymentMethodMinAmount) && $orderTotal < $paymentMethodMinAmount) {
            return true;
        }

        if (!empty($paymentMethodMaxAmount) && $orderTotal > $paymentMethodMaxAmount) {
            return true;
        }

        if (!empty($paymentMethodCountries) && !in_array($orderCountryId, $paymentMethodCountries, true)) {
            return true;
        }

        if (!empty($paymentMethodCurrencies) && !in_array($orderCurrencyId, $paymentMethodCurrencies, true)) {
            return true;
        }

        if (!empty($paymentMethodCustomerGroups) && !in_array($orderCustomerGroups, $paymentMethodCustomerGroups, true)) {
            return true;
        }

        if (!$isCartSplitted && !$isVirtual && !empty($paymentMethodCarriers) && !in_array($orderCarrierId, $paymentMethodCarriers, true)) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    private function getPaymentOptionClassNamesFromDirectory(): array
    {
        $classNames = [];
        $finder = new Finder();
        $files = $finder->files()->notName('index.php')->name('*.php')->in(self::PAYMENT_OPTIONS_DIR);
        foreach ($files as $file) {
            $classNames[] = str_replace(".php", "", self::PAYMENT_OPTIONS_NAMESPACE.$file->getFilename());
        }
        return $classNames;
    }
}
