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
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\PrestaShop\Services;

use Address;
use Cart;
use Configuration;
use Customer;
use Exception;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidDataInitializationException;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BaseBrandedPaymentOption;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultisafepayOfficial;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use SmartyException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class PaymentOptionService
 * @package MultiSafepay\PrestaShop\Services
 */
class PaymentOptionService
{

    /**
     * Credit and debit cards gateway IDs
     *
     * @var array
     */
    public const CREDIT_CARD_GATEWAYS = [ 'VISA', 'MASTERCARD', 'AMEX', 'MAESTRO' ];

    /**
     * Payment methods with some specific features
     *
     * @var array
     */
    public const PAYMENTS_WITH_SPECIFIC_FEATURES = ['APPLEPAY', 'BANKTRANS', 'GOOGLEPAY', 'IN3', 'IN3B2B'];

    /**
     *  Cache expiration time in seconds
     *
     * @var int
     */
    public const CACHE_EXPIRE_TIME = 86400;

    /**
     *  Cache name
     *
     * @var string
     */
    public const CACHE_NAME = 'multisafepay_payment_methods_cache';

    /**
     * @var string
     */
    public const PAYMENT_OPT_NAMESPACE = "MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\\";

    /**
     * @var array|null
     */
    private $paymentOptions;

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
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function getMultiSafepayPaymentOptions(): array
    {
        if (!isset($this->paymentOptions)) {
            $paymentOptions = [];
            $paymentMethods = $this->getMultiSafepayPaymentMethods();

            /** @var PaymentMethod $paymentMethod */
            foreach ($paymentMethods as $paymentMethod) {
                $childClass = $this->checkChildClassName($paymentMethod->getId());

                if (!empty($childClass)) {
                    /** @var BasePaymentOption[] $paymentOptions */
                    $paymentOptions[] = (new $childClass($paymentMethod, $this->module));
                } else {
                    /** @var BasePaymentOption[] $paymentOptions */
                    $paymentOptions[] = new BasePaymentOption($paymentMethod, $this->module);
                }

                if (!Configuration::get('MULTISAFEPAY_OFFICIAL_GROUP_CREDITCARDS')) {
                    $brandsList = $paymentMethod->getBrands() ?: [];
                    if (!empty($brandsList)) {
                        foreach ($brandsList as $brandedPaymentMethod) {
                            $paymentOptions[] = new BaseBrandedPaymentOption($paymentMethod, $this->module, $brandedPaymentMethod);
                        }
                    }
                }
            }

            $paymentOptions = $this->sortOrderPaymentOptions($paymentOptions);
            $this->paymentOptions = $paymentOptions;
        }

        return $this->paymentOptions;
    }

    /**
     * @param string $gatewayCode
     *
     * @return BasePaymentOption
     */
    public function getMultiSafepayPaymentOption(string $gatewayCode): ?BasePaymentOption
    {
        foreach ($this->getMultiSafepayPaymentOptions() as $paymentOption) {
            if ($paymentOption->getGatewayCode(true) === $gatewayCode) {
                return $paymentOption;
            }
        }

        return null;
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
     * Get all active MultiSafepay payment options and filter the duplicated branded names
     *
     * @param array $paymentMethods
     *
     * @return array
     */
    private function getDuplicatedBrandedGateways(array $paymentMethods): array
    {
        $paymentGatewayCounts = [];
        foreach ($paymentMethods as $paymentMethod) {
            if (empty($paymentMethod->parentGateway)) {
                continue;
            }
            /** @var BaseBrandedPaymentOption $paymentMethod */
            $primaryGateway = $paymentMethod->gatewayCode;

            if (!isset($paymentGatewayCounts[$primaryGateway])) {
                $paymentGatewayCounts[$primaryGateway] = 0;
            }

            $paymentGatewayCounts[$primaryGateway]++;
        }

        $duplicatedBrandedGateways = array_filter($paymentGatewayCounts, static function ($count) {
            return $count > 1;
        });

        return array_keys($duplicatedBrandedGateways);
    }

    /**
     *  Filter the duplicated brand names to add just their names only when necessary
     *
     * @param BasePaymentOption $paymentMethod
     * @param array $duplicatedBrandedNames
     *
     * @return string
     */
    private function filterDuplicatedBrandedNames(BasePaymentOption $paymentMethod, array $duplicatedBrandedNames): string
    {
        $isDuplicated = in_array($paymentMethod->gatewayCode, $duplicatedBrandedNames, true);
        return $isDuplicated ? $paymentMethod->getFrontEndName() : $this->getFilteredFrontEndName($paymentMethod);
    }

    /**
     * Get the filtered checkout name without the parent name if any
     *
     * @param BasePaymentOption $paymentMethod
     *
     * @return string
     */
    private function getFilteredFrontEndName(BasePaymentOption $paymentMethod): string
    {
        $frontName = $paymentMethod->getFrontEndName();
        $parentName = $paymentMethod->parentName;
        if (!empty($parentName) && (strpos($frontName, $parentName) !== false)) {
            return $paymentMethod->getBrandName();
        }

        return $frontName;
    }

    /**
     * Return an array of MultiSafepay PaymentOptions
     *
     * @param   Cart  $cart
     *
     * @return array
     * @throws SmartyException|Exception
     */
    public function getFilteredMultiSafepayPaymentOptions(Cart $cart): array
    {
        $paymentOptions = [];
        /** @var BasePaymentOption[] $paymentMethods */
        $paymentMethods = $this->getActivePaymentOptions();

        $duplicatedBrandedNames = $this->getDuplicatedBrandedGateways($paymentMethods);

        foreach ($paymentMethods as $paymentMethod) {
            if ($this->excludePaymentOptionByPaymentOptionSettings($paymentMethod, $cart)) {
                continue;
            }

            $option = new PaymentOption();
            $option->setForm($this->module->getMultiSafepayPaymentOptionForm($paymentMethod));
            $option->setModuleName($paymentMethod->getGatewayCode());
            $option->setCallToActionText($this->filterDuplicatedBrandedNames($paymentMethod, $duplicatedBrandedNames));
            $option->setAction($paymentMethod->getAction());
            if (!empty($paymentMethod->getLogo())) {
                $option->setLogo($paymentMethod->getLogo());
            }
            if ($paymentMethod->getDescription()) {
                $option->setAdditionalInformation($paymentMethod->getDescription());
            }
            $paymentOptions[] = $option;
        }

        return $paymentOptions;
    }

    /**
     * Filter the payment option according to their settings and the cart properties
     *
     * @param BasePaymentOption $paymentMethod
     * @param Cart $cart
     *
     * @return bool
     * @throws Exception
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function excludePaymentOptionByPaymentOptionSettings(BasePaymentOption $paymentMethod, Cart $cart): bool
    {
        $orderTotal             = $cart->getOrderTotal();
        $orderCountryId         = (new Address($cart->id_address_invoice))->id_country;
        $orderCurrencyId        = $cart->id_currency;
        $orderCustomerGroups    = (new Customer($cart->id_customer))->id_default_group;
        $orderCarrierId         = $cart->id_carrier;
        $isVirtual              = $cart->isVirtualCart();
        $isCartSplit            = $cart->getNbOfPackages() > 1;

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

        if (!empty($paymentMethodCountries) && !in_array((string)$orderCountryId, $paymentMethodCountries, true)) {
            return true;
        }

        if (!empty($paymentMethodCurrencies) && !in_array((string)$orderCurrencyId, $paymentMethodCurrencies, true)) {
            return true;
        }

        if (!empty($paymentMethodCustomerGroups) && !in_array((string)$orderCustomerGroups, $paymentMethodCustomerGroups, true)) {
            return true;
        }

        if (!$isCartSplit && !$isVirtual && !empty($paymentMethodCarriers) && !in_array((string)$orderCarrierId, $paymentMethodCarriers, true)) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getMultiSafepayPaymentMethods(): array
    {
        try {
            return $this->fetchPaymentMethods();
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'error',
                $exception,
                'General Error'
            );
        }

        return [];
    }

    /**
     * Fetch the payment methods from the SDK
     *
     * @return array
     * @throws InvalidDataInitializationException
     */
    private function fetchPaymentMethods(): array
    {
        $sdkService = new SdkService();

        $apiKey = $sdkService->getApiKey();
        if (empty($apiKey)) {
            return [];
        }

        $options['group_cards'] = Configuration::get('MULTISAFEPAY_OFFICIAL_GROUP_CREDITCARDS') ?: '0';
        if (isset($_POST['MULTISAFEPAY_OFFICIAL_GROUP_CREDITCARDS']) && defined('_PS_ADMIN_DIR_')) {
            $newGroupCardsValue = Tools::getValue('MULTISAFEPAY_OFFICIAL_GROUP_CREDITCARDS') ? '1' : '0';
            if ($options['group_cards'] !== $newGroupCardsValue) {
                Configuration::updateValue('MULTISAFEPAY_OFFICIAL_GROUP_CREDITCARDS', $newGroupCardsValue);
                $options['group_cards'] = $newGroupCardsValue;
            }
        }

        $sdk = null;

        try {
            $sdk = $sdkService->getSdk();
            $cache = new FilesystemAdapter();
            $environment = $sdkService->getTestMode() ? 'test' : 'live';
            $item = $cache->getItem(self::CACHE_NAME . '_' . $environment);

            if (($item instanceof CacheItemInterface) &&
                !defined('_PS_ADMIN_DIR_') &&
                $item->isHit()
            ) {
                return $this->getCachedData($item);
            }

            if (!is_null($sdk)) {
                $paymentMethods = $sdk->getPaymentMethodManager()->getPaymentMethods(true, $options) ?: [];
                $this->cachePaymentMethods($paymentMethods, $cache, $item);
                return $paymentMethods;
            }

            LoggerHelper::log(
                'error',
                'Error trying to get the MultiSafepay PHP-SDK',
                true
            );
            return [];
        } catch (InvalidArgumentException $cacheException) {
            LoggerHelper::logException(
                'error',
                $cacheException,
                'Error using class FilesystemAdapter. Direct call to the API had to be used'
            );
        } catch (ApiException | ClientExceptionInterface $exception) {
            LoggerHelper::logException(
                'error',
                $exception,
                'Client exception from the MultiSafepay API: '
            );
            return [];
        }

        // If the cache fails and no previous error occurred in the SDK, call the API directly
        try {
            return $sdk->getPaymentMethodManager()->getPaymentMethods(true, $options) ?: [];
        } catch (ApiException | ClientExceptionInterface $exception) {
            LoggerHelper::logException(
                'error',
                $exception,
                'Error from the MultiSafepay API once FilesystemAdapter class failed'
            );
            return [];
        }
    }

    /**
     * Helper method to get cached data safely
     *
     * @param CacheItemInterface $item
     *
     * @return array
     */
    private function getCachedData(CacheItemInterface $item): array
    {
        $cachedData = $item->get();

        return is_array($cachedData) && !empty($cachedData) ? $cachedData : [];
    }

    /**
     * Helper method to cache payment methods
     *
     * @param array $paymentMethods
     * @param FilesystemAdapter|null $cache
     * @param CacheItemInterface|null $item
     */
    private function cachePaymentMethods(
        array $paymentMethods,
        ?FilesystemAdapter $cache,
        ?CacheItemInterface $item
    ): void {
        if (($cache instanceof FilesystemAdapter) &&
            ($item instanceof CacheItemInterface)
        ) {
            $item->set($paymentMethods);
            $item->expiresAfter(self::CACHE_EXPIRE_TIME);
            $cache->save($item);
        }
    }

    /**
     * Determines if a gateway code has a special class implementation
     *
     * @param string $gatewayCode
     * @return string The fully qualified class name
     */
    private function checkChildClassName(string $gatewayCode): string
    {
        return in_array($gatewayCode, self::PAYMENTS_WITH_SPECIFIC_FEATURES) ?
            self::PAYMENT_OPT_NAMESPACE . ucfirst(strtolower($gatewayCode)) : '';
    }

    /**
     * Sort the payment options by sort order position and name
     *
     * Uses BasePaymentOption::getSortOrderPosition() for sorting
     *
     * @param BasePaymentOption[] $paymentOptions
     * @return BasePaymentOption[]
     */
    public function sortOrderPaymentOptions(array $paymentOptions): array
    {
        uasort($paymentOptions, static function ($a, $b) {
            /** @var BasePaymentOption $a */
            /** @var BasePaymentOption $b */
            return $a->getSortOrderPosition() - $b->getSortOrderPosition() ?: strcasecmp($a->getName(), $b->getName());
        });
        return $paymentOptions;
    }
}
