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

namespace MultiSafepay\PrestaShop\Services;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay;
use MultisafepayOfficial;
use Cart;
use Address;
use Customer;
use Media;
use Context;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Symfony\Component\Finder\Finder;
use Tools;

/**
 * Class PaymentOptionService
 * @package MultiSafepay\PrestaShop\Services
 */
class PaymentOptionService
{
    public const PAYMENT_OPTIONS_NAMESPACE = "MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\\";
    public const PAYMENT_OPTIONS_DIR = _PS_ROOT_DIR_.'/modules/multisafepayofficial/src/PaymentOptions/PaymentMethods';

    /**
     * @var MultisafepayOfficial
     */
    private $module;

    /**
     * SdkService constructor.
     */
    public function __construct(MultisafepayOfficial $module)
    {
        $this->module = $module;
    }

    /**
     * Get all MultiSafepay payment options
     *
     * @return array
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function getMultiSafepayPaymentOptions(): array
    {
        $paymentOptions = [];
        foreach ($this->getPaymentOptionClassNamesFromDirectory() as $className) {
            $paymentOptions[] = new $className($this->module);
        }
        uasort($paymentOptions, function ($a, $b) {
            return $a->getSortOrderPosition() - $b->getSortOrderPosition() ?: strcasecmp($a->getName(), $b->getName());
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
        if (empty($gatewayCode)) {
            return new MultiSafepay($this->module);
        }

        foreach ($this->getPaymentOptionClassNamesFromDirectory() as $className) {
            $paymentOption = new $className($this->module);
            if ($paymentOption->getGatewayCode() === $gatewayCode) {
                return $paymentOption;
            }
        }

        return new MultiSafepay($this->module);
    }

    /**
     * Get all active MultiSafepay payment options
     *
     * @return array
     */
    public function getActivePaymentOptions(): array
    {
        $paymentOptions = [];
        foreach ($this->getMultiSafepayPaymentOptions() as $paymentOption) {
            if ($paymentOption->isActive()) {
                $paymentOptions[] = $paymentOption;
            }
        }

        return $paymentOptions;
    }

    /**
     * Return  an array of MultiSafepay PaymentOptions
     *
     * @return array
     */
    public function getFilteredMultiSafepayPaymentOptions(Cart $cart): array
    {
        $paymentOptions = [];
        /** @var BasePaymentOption[] $paymentMethods */
        $paymentMethods = $this->getActivePaymentOptions();
        foreach ($paymentMethods as $paymentMethod) {
            if ($this->excludePaymentOptionByPaymentOptionSettings($paymentMethod, $cart)) {
                continue;
            }

            $option = new PaymentOption();
            $option->setModuleName($paymentMethod->getGatewayCode());
            $option->setCallToActionText($paymentMethod->getFrontEndName());
            $option->setAction($paymentMethod->getAction());
            $option->setForm($this->module->getMultiSafepayPaymentOptionForm($paymentMethod));
            if (!empty($paymentMethod->getLogo())) {
                $option->setLogo($this->getLogoByName($paymentMethod->getLogo()));
            }
            if ($paymentMethod->getDescription()) {
                $option->setAdditionalInformation($paymentMethod->getDescription());
            }

            $paymentOptions[] = $option;
        }
        return $paymentOptions;
    }


    /**
     * @param string $name
     * @return string
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    private function getLogoByName(string $name): string
    {
        // If Generic Gateway, this will return a full URL
        if (file_exists($name)) {
            return Media::getMediaPath($name);
        }

        // Logo by language
        $logoLocale = _PS_MODULE_DIR_ . $this->module->name . '/views/img/' . str_replace('.png', '', $name) . '-'. Tools::strtolower(Tools::substr(Context::getContext()->language->locale, 0, 2)).'.png';
        if (file_exists($logoLocale)) {
            return Media::getMediaPath($logoLocale);
        }

        // Default logo
        if (file_exists(_PS_MODULE_DIR_ . $this->module->name . '/views/img/' . $name)) {
            return Media::getMediaPath(_PS_MODULE_DIR_ . $this->module->name . '/views/img/' . $name);
        }

        return '';
    }

    /**
     * Filter the payment option according with their settings and the cart properties
     *
     * @param BasePaymentOption $paymentMethod
     * @param Cart $cart
     * @return bool
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    private function excludePaymentOptionByPaymentOptionSettings(BasePaymentOption $paymentMethod, Cart $cart)
    {
        $orderTotal             = $cart->getOrderTotal();
        $orderCountryId         = (new Address($cart->id_address_invoice))->id_country;
        $orderCurrencyId        = $cart->id_currency;
        $orderCustomerGroups    = (new Customer($cart->id_customer))->id_default_group;
        $orderCarrierId         = $cart->id_carrier;
        $isVirtual              = $cart->isVirtualCart();
        $isCartSplitted         = $cart->getNbOfPackages() > 1;

        $paymentMethodSettings = $paymentMethod->getGatewaySettings();

        $paymentMethodStatus         = (bool) $paymentMethodSettings['MULTISAFEPAY_OFFICIAL_GATEWAY_' . $paymentMethod->getUniqueName()]['value'];
        $paymentMethodMinAmount      = (float) $paymentMethodSettings['MULTISAFEPAY_OFFICIAL_MIN_AMOUNT_' . $paymentMethod->getUniqueName()]['value'];
        $paymentMethodMaxAmount      = (float) $paymentMethodSettings['MULTISAFEPAY_OFFICIAL_MAX_AMOUNT_' . $paymentMethod->getUniqueName()]['value'];
        $paymentMethodCountries      = $paymentMethodSettings['MULTISAFEPAY_OFFICIAL_COUNTRIES_' . $paymentMethod->getUniqueName()]['value'];
        $paymentMethodCurrencies     = $paymentMethodSettings['MULTISAFEPAY_OFFICIAL_CURRENCIES_' . $paymentMethod->getUniqueName()]['value'];
        $paymentMethodCustomerGroups = $paymentMethodSettings['MULTISAFEPAY_OFFICIAL_CUSTOMER_GROUPS_' . $paymentMethod->getUniqueName()]['value'];
        $paymentMethodCarriers       = $paymentMethodSettings['MULTISAFEPAY_OFFICIAL_CARRIERS_' . $paymentMethod->getUniqueName()]['value'];

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
        $files = $finder->files()->notName('index.php')->notName('IngHomePay.php')->name('*.php')->in(self::PAYMENT_OPTIONS_DIR);
        foreach ($files as $file) {
            $classNames[] = str_replace(".php", "", self::PAYMENT_OPTIONS_NAMESPACE.$file->getFilename());
        }
        return $classNames;
    }
}
