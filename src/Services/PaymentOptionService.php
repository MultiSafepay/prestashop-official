<?php

namespace MultiSafepay\PrestaShop\Services;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay;
use Multisafepay as MultiSafepayModule;
use Cart;
use Address;
use Customer;
use Media;
use Context;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Symfony\Component\Finder\Finder;

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
            return strcmp($a->getSortOrderPosition(), $b->getSortOrderPosition());
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
            if ($paymentOption->getGatewayCode() === $gatewayCode) {
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
        /** @var BasePaymentOption[] $paymentMethods */
        $paymentMethods = $this->getMultiSafepayPaymentOptions();
        foreach ($paymentMethods as $paymentMethod) {
            if ($this->excludePaymentOptionByPaymentOptionSettings($paymentMethod, $cart)) {
                continue;
            }

            $option = new PaymentOption();
            $option->setCallToActionText($paymentMethod->getFrontEndName());
            $option->setAction($paymentMethod->getAction());
            $option->setForm($this->module->getMultiSafepayPaymentOptionForm($paymentMethod->getGatewayCode(), $paymentMethod->getInputFields()));
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
     */
    private function getLogoByName(string $name): string
    {

        // Logo by language
        $logoLocale = _PS_MODULE_DIR_ . $this->module->name . '/views/img/' . str_replace('.png', '', $name) . '-'. strtolower(substr(Context::getContext()->language->locale, 0, 2)).'.png';
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
