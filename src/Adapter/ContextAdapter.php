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

namespace MultiSafepay\PrestaShop\Adapter;

use Cart;
use Configuration;
use Currency;
use Employee;
use Exception;
use Language;
use Link;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use RuntimeException;
use Shop;
use Smarty;
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Adapter for accessing PrestaShop context services in a module-friendly way
 *
 * Provides static helpers for language, customer, cart, currency, shop, link, smarty, and controller languages
 *
 * Following ADR #24 - Context Refactorization best practices where possible
 *
 * @see https://github.com/PrestaShop/ADR/blob/master/0024-context-refacto.md
 */
class ContextAdapter
{
    /**
     * Get Smarty instance with a fallback chain
     *
     * First, tries to use LegacyContext::getSmarty(), falls back to creating a new instance
     *
     * @return Smarty|null
     */
    public static function getSmarty(): ?Smarty
    {
        try {
            LoggerHelper::log(
                'info',
                'Attempting to get Smarty instance from LegacyContext'
            );

            $contextAdapter = new LegacyContext();
            $smarty = $contextAdapter->getSmarty();

            if ($smarty instanceof Smarty) {
                LoggerHelper::log(
                    'info',
                    'Successfully retrieved Smarty instance from LegacyContext'
                );
                return $smarty;
            }
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'warning',
                $exception,
                'Failed to get Smarty from LegacyContext, creating custom instance'
            );
        }
        return null;
    }

    /**
     * Get language context following ADR #24 context refactorization
     *
     * Provides a graceful fallback chain: Context → LegacyContext → Configuration
     *
     * @param object|null $context Optional context object
     * @return int Language ID
     */
    public static function getLanguageId(?object $context = null): int
    {
        // Method 1: Provided context (preferred method)
        if (!empty($context->language) && (int)$context->language->id > 0) {
            LoggerHelper::log(
                'info',
                'Retrieved language ID: ' . $context->language->id . ' from provided context'
            );
            return (int)$context->language->id;
        }

        if ($context) {
            LoggerHelper::log(
                'warning',
                'Provided context does not contain a valid language'
            );
        } else {
            LoggerHelper::log(
                'info',
                'No context provided to getLanguageId() - using LegacyContext fallback'
            );
        }

        // Method 2: Legacy context fallback using LegacyContext class directly
        try {
            $contextAdapter = new LegacyContext();
            $language = $contextAdapter->getLanguage();
            if (!empty($language->id)) {
                LoggerHelper::log(
                    'info',
                    'Retrieved language ID from LegacyContext adapter: ' . $language->id
                );
                return (int)$language->id;
            }
            LoggerHelper::log(
                'warning',
                'LegacyContext adapter returned invalid language'
            );
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'warning',
                $exception,
                'Error accessing LegacyContext adapter'
            );
        }

        // Method 3: Configuration fallback (always available)
        return self::getDefaultLanguageId();
    }

    /**
     * Get the default language ID from configuration
     *
     * @return int
     */
    public static function getDefaultLanguageId(): int
    {
        try {
            $defaultLanguageId = (int)Configuration::get('PS_LANG_DEFAULT');
            if ($defaultLanguageId > 0) {
                return $defaultLanguageId;
            }
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'warning',
                $exception,
                'Could not get default language from configuration'
            );
        }

        // Final fallback: Use hardcoded language ID 1 (usually English)
        return 1;
    }

    /**
     * Get the current currency object with intelligent fallbacks
     *
     * @param object|null $context The context object
     * @return Currency
     */
    public static function getCurrency(?object $context = null): Currency
    {
        // Try 1: Provided context parameter (preferred method)
        if (!empty($context->currency) && (int)$context->currency->id > 0) {
            LoggerHelper::log(
                'info',
                'Retrieved currency ID: ' . $context->currency->id . ' from provided context'
            );
            return new Currency($context->currency->id);
        }

        if ($context) {
            LoggerHelper::log(
                'warning',
                'Provided context does not contain valid currency'
            );
        } else {
            LoggerHelper::log(
                'info',
                'No context provided to getCurrency() - using LegacyContext fallback'
            );
        }

        // Try 2: Legacy context fallback using LegacyContext class directly
        try {
            $contextAdapter = new LegacyContext();
            $legacyContext = $contextAdapter->getContext();
            if (!empty($legacyContext->currency) && (int)$legacyContext->currency->id > 0) {
                LoggerHelper::log(
                    'info',
                    'Retrieved currency ID: ' . $legacyContext->currency->id . ' from LegacyContext adapter'
                );
                return $legacyContext->currency;
            }
            LoggerHelper::log(
                'warning',
                'LegacyContext adapter returned invalid currency'
            );
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'warning',
                $exception,
                'Error accessing LegacyContext adapter'
            );
        }

        // Try 3: Default currency from configuration fallback (always available)
        try {
            $defaultCurrencyId = (int)Configuration::get('PS_CURRENCY_DEFAULT');
            if ($defaultCurrencyId > 0) {
                LoggerHelper::log(
                    'info',
                    'Retrieved default currency ID: ' . $defaultCurrencyId . ' from configuration'
                );
                return new Currency($defaultCurrencyId);
            }
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'warning',
                $exception,
                'Error retrieving default currency from configuration'
            );
        }

        // Final fallback: hardcoded (should never reach here)
        LoggerHelper::log(
            'warning',
            'All currency retrieval methods failed, using hardcoded fallback: 1'
        );
        return new Currency(1);
    }

    /**
     * Get the current shop object with fallbacks
     *
     * @param object|null $cart The cart object
     * @return Shop
     */
    public static function getShop(?object $cart = null): Shop
    {
        // Try 1: Provided cart parameter (preferred method)
        if ($cart && isset($cart->id_shop) && (int)$cart->id_shop > 0) {
            LoggerHelper::log(
                'info',
                'Successfully retrieved shop ID: ' . $cart->id_shop . ' from provided cart'
            );
            return new Shop($cart->id_shop);
        }

        if ($cart) {
            LoggerHelper::log(
                'warning',
                'Provided cart does not contain valid shop'
            );
        } else {
            LoggerHelper::log(
                'warning',
                'No cart provided to getShop() - using fallback methods'
            );
        }

        // Try 2: Default shop from configuration fallback (always available)
        try {
            $defaultShopId = (int)Configuration::get('PS_SHOP_DEFAULT');
            if ($defaultShopId > 0) {
                LoggerHelper::log(
                    'info',
                    'Using default shop ID: ' . $defaultShopId . ' from configuration'
                );
                return new Shop($defaultShopId);
            }
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'warning',
                $exception,
                'Error getting default shop from configuration'
            );
        }

        // Final fallback: hardcoded (should never reach here)
        LoggerHelper::log(
            'warning',
            'All shop methods failed, using hardcoded fallback: 1'
        );
        return new Shop(1);
    }

    /**
     * Get the current employee object from the context service
     *
     * Returns null if no employee is logged in (frontend context)
     *
     * @param object|null $context The context object
     * @return Employee|null
     */
    public static function getEmployee(?object $context = null): ?Employee
    {
        // Try 1: Provided context parameter (preferred method)
        if (!empty($context->employee) && (int)$context->employee->id > 0) {
            LoggerHelper::log(
                'info',
                'Successfully retrieved employee from provided context: ' . $context->employee->id
            );
            return $context->employee;
        }

        // Try 2: LegacyContext adapter fallback using class directly
        try {
            $contextAdapter = new LegacyContext();
            $legacyContext = $contextAdapter->getContext();
            if (!empty($legacyContext->employee) && (int)$legacyContext->employee->id > 0) {
                LoggerHelper::log(
                    'info',
                    'Successfully retrieved employee from LegacyContext adapter with ID: ' .
                    $legacyContext->employee->id
                );
                return $legacyContext->employee;
            }
            LoggerHelper::log(
                'warning',
                'LegacyContext adapter returned invalid employee'
            );
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'warning',
                $exception,
                'Error accessing LegacyContext adapter'
            );
        }

        // No employee found - return null if not in admin context
        LoggerHelper::log(
            'info',
            'No employee found in any context - likely frontend context'
        );
        return null;
    }

    /**
     * Get the Link object for generating URLs.
     *
     * @return Link
     */
    public static function getLink(): Link
    {
        try {
            LoggerHelper::log(
                'info',
                'Creating new Link object for URL generation'
            );
            return new Link();
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'error',
                $exception,
                'Error creating Link object'
            );
            throw new RuntimeException('Error creating Link object');
        }
    }

    /**
     * Get the available languages for the current controller from the context service
     *
     * @param object|null $context
     * @return array
     * @throws RuntimeException If the context service is not available
     */
    public static function getControllerLanguages(?object $context = null): array
    {
        try {
            // Try 1: get languages from LegacyContext adapter directly
            $contextAdapter = new LegacyContext();
            if (method_exists($contextAdapter, 'getLanguages')) {
                $languages = $contextAdapter->getLanguages();
                LoggerHelper::log(
                    'info',
                    'Successfully retrieved languages from LegacyContext adapter, count: ' . count($languages)
                );
            } else {
                // Try 2: get languages from the database directly
                LoggerHelper::log(
                    'warning',
                    'LegacyContext adapter not available, using database fallback for languages'
                );

                try {
                    $shopId = $context && isset($context->shop) ? $context->shop->id : null;
                    if (!empty($shopId) && is_numeric($shopId)) {
                        $languages = Language::getLanguages(true, (int)$shopId);
                    } else {
                        $languages = Language::getLanguages();
                    }

                    if (!empty($languages)) {
                        LoggerHelper::log(
                            'info',
                            'Successfully retrieved languages from database fallback, count: ' . count($languages)
                        );
                    }
                } catch (Exception $exception) {
                    LoggerHelper::logException(
                        'warning',
                        $exception,
                        'Error getting languages from database fallback'
                    );
                    $languages = [];
                }
            }

            // Add is_default key to each language for Smarty template compatibility
            if (!empty($languages)) {
                $defaultLanguageId = self::getDefaultLanguageId();

                foreach ($languages as &$language) {
                    $language['is_default'] = ((int)$language['id_lang'] === $defaultLanguageId) ? '1' : '0';
                }
                unset($language); // Break the reference

                LoggerHelper::log(
                    'info',
                    'Added is_default key to languages for Smarty template compatibility'
                );
                return $languages;
            }

            LoggerHelper::log(
                'error',
                'No language services available and database fallback failed'
            );
            throw new RuntimeException('Language services not available');
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'error',
                $exception,
                'Error getting controller languages from context services'
            );
            throw new RuntimeException('Language services not available');
        }
    }

    /**
     * Get the current cart through Customer ID.
     *
     * @param int $customerId Customer ID (must be obtained at controller level)
     * @return Cart|null
     */
    public static function getCurrentCartByCustomer(int $customerId): ?Cart
    {
        try {
            LoggerHelper::log(
                'info',
                'Attempting to get cart for customer ID: ' . $customerId
            );

            if (!$customerId || $customerId <= 0) {
                LoggerHelper::log(
                    'warning',
                    'Invalid customer ID: ' . $customerId . ' provided'
                );
                return null;
            }

            // Use PrestaShop's native method to get the last non-ordered cart
            $cartId = Cart::lastNoneOrderedCart($customerId);
            LoggerHelper::log(
                'info',
                'Cart::lastNoneOrderedCart() returned cart ID: ' . ($cartId ?: 'null')
            );

            if ($cartId) {
                $cart = new Cart($cartId);
                if ($cart->id) {
                    LoggerHelper::log(
                        'info',
                        'Successfully loaded cart ID: ' . $cart->id . ' for customer ID: ' . $customerId
                    );
                    return $cart;
                }
            }

            // Fallback: get all customer carts and find the most recent one
            LoggerHelper::log(
                'info',
                'Trying fallback method to get customer carts for customer ID: ' . $customerId
            );
            $customerCarts = Cart::getCustomerCarts($customerId, false); // false = only non-ordered carts
            if (!empty($customerCarts)) {
                $latestCart = $customerCarts[0]; // Already ordered by date_add DESC
                $cart = new Cart($latestCart['id_cart']);
                if ($cart->id) {
                    LoggerHelper::log(
                        'info',
                        'Successfully loaded fallback cart ID: ' . $cart->id . ' for customer ID: ' . $customerId
                    );
                    return $cart;
                }
            } else {
                LoggerHelper::log(
                    'warning',
                    'No carts found for customer ID: ' . $customerId
                );
            }
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'error',
                $exception,
                'Error getting the current cart for customer ID: ' . $customerId
            );
            return null;
        }

        LoggerHelper::log(
            'warning',
            'No valid cart found for customer ID: ' . $customerId
        );
        return null;
    }

    /**
     * Get the current locale from the language context service
     *
     * @param object|null $context The context object
     * @return string|null
     */
    public static function getLocale(?object $context = null): ?string
    {
        try {
            $idLang = self::getLanguageId($context);
            if ($idLang) {
                $localeResult = Language::getLocaleById($idLang);
                $locale = $localeResult ? Tools::substr($localeResult, 0, 2) : null;
                LoggerHelper::log(
                    'info',
                    'Successfully retrieved locale: ' . ($locale ?: 'null') . ' for language ID: ' . $idLang
                );
                return $locale;
            }
            LoggerHelper::log(
                'warning',
                'No language ID available to get locale'
            );
            return null;
        } catch (Exception $exception) {
            LoggerHelper::logException(
                'error',
                $exception,
                'Error getting locale from language context'
            );
            return null;
        }
    }
}
