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

namespace MultiSafepay\PrestaShop\Helper;

use InvalidArgumentException;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\Exception\InvalidDataInitializationException;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Helper class for creating and managing payment method configurations
 */
class PaymentMethodConfigHelper
{
    /**
     * Payment method constants
     */
    private const PAYMENT_METHOD_ID = 'MULTISAFEPAY';
    private const PAYMENT_METHOD_NAME = 'MultiSafepay';
    private const PAYMENT_METHOD_TYPE = 'payment-method';

    /**
     * Icon URL constants
     */
    private const ICON_BASE_URL = 'https://media.dev.multisafepay.com/img/methods';
    private const ICON_LARGE_PATH = '/3x/multisafepay.png';
    private const ICON_MEDIUM_PATH = '/2x/multisafepay.png';
    private const ICON_VECTOR_PATH = '/svg/multisafepay.svg';

    /**
     * Default configuration values
     */
    private const DEFAULT_MIN_AMOUNT = 0;
    private const DEFAULT_MAX_AMOUNT = null;
    private const DEFAULT_SHOPPING_CART_REQUIRED = false;

    /**
     * Create a default MultiSafepay payment method configuration
     *
     * @return PaymentMethod
     * @throws InvalidArgumentException If the configuration is invalid
     * @throws InvalidDataInitializationException
     */
    public static function createDefaultPaymentMethod(): PaymentMethod
    {
        $config = self::getDefaultConfig();
        self::validateConfig($config);

        return new PaymentMethod($config);
    }

    /**
     * Get the default configuration array for MultiSafepay payment method
     *
     * @return array
     */
    private static function getDefaultConfig(): array
    {
        return [
            'additional_data' => self::getAdditionalDataConfig(),
            'allowed_amount' => self::getAllowedAmountConfig(),
            'allowed_countries' => self::getAllowedCountriesConfig(),
            'allowed_currencies' => self::getAllowedCurrenciesConfig(),
            'apps' => self::getAppsConfig(),
            'brands' => self::getBrandsConfig(),
            'description' => self::getDescriptionConfig(),
            'icon_urls' => self::getIconUrlsConfig(),
            'id' => self::PAYMENT_METHOD_ID,
            'label' => self::getLabelConfig(),
            'manual_capture' => self::getManualCaptureConfig(),
            'name' => self::PAYMENT_METHOD_NAME,
            'preferred_countries' => self::getPreferredCountriesConfig(),
            'required_customer_data' => self::getRequiredCustomerDataConfig(),
            'shopping_cart_required' => self::DEFAULT_SHOPPING_CART_REQUIRED,
            'tokenization' => self::getTokenizationConfig(),
            'type' => self::PAYMENT_METHOD_TYPE,
        ];
    }

    /**
     * Validate payment method configuration
     *
     * @param array $config Configuration to validate
     * @throws InvalidArgumentException If the configuration is invalid
     */
    private static function validateConfig(array $config): void
    {
        $requiredFields = ['id', 'name', 'type', 'allowed_amount', 'apps'];

        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                throw new InvalidArgumentException('Missing required field: ' . $field);
            }
        }

        if (!is_array($config['allowed_amount'])) {
            throw new InvalidArgumentException('allowed_amount must be an array');
        }

        if (!is_array($config['apps'])) {
            throw new InvalidArgumentException('apps must be an array');
        }
    }

    /**
     * Get additional data configuration
     *
     * @return array
     */
    private static function getAdditionalDataConfig(): array
    {
        return [];
    }

    /**
     * Get allowed amount configuration
     *
     * @return array
     */
    private static function getAllowedAmountConfig(): array
    {
        return [
            'max' => self::DEFAULT_MAX_AMOUNT,
            'min' => self::DEFAULT_MIN_AMOUNT,
        ];
    }

    /**
     * Get allowed country configuration
     *
     * @return array
     */
    private static function getAllowedCountriesConfig(): array
    {
        return [];
    }

    /**
     * Get allowed currency configuration
     *
     * @return array
     */
    private static function getAllowedCurrenciesConfig(): array
    {
        return [''];
    }

    /**
     * Get app configuration with all supported features
     *
     * @return array
     */
    private static function getAppsConfig(): array
    {
        return [
            'fastcheckout' => self::getFastCheckoutConfig(),
            'payment_components' => self::getPaymentComponentsConfig(),
        ];
    }

    /**
     * Get FastCheckout app configuration
     *
     * @return array
     */
    private static function getFastCheckoutConfig(): array
    {
        return [
            'is_enabled' => false,
            'qr' => [
                'supported' => false,
            ],
        ];
    }

    /**
     * Get Payment Components app configuration
     *
     * @return array
     */
    private static function getPaymentComponentsConfig(): array
    {
        return [
            'has_fields' => false,
            'is_enabled' => false,
            'qr' => [
                'supported' => false,
            ],
        ];
    }

    /**
     * Get brands configuration
     *
     * @return array
     */
    private static function getBrandsConfig(): array
    {
        return [];
    }

    /**
     * Get description configuration
     *
     * @return null
     */
    private static function getDescriptionConfig()
    {
        return null;
    }

    /**
     * Get icon URLs configuration with proper URL construction
     *
     * @return array
     */
    private static function getIconUrlsConfig(): array
    {
        return [
            'large' => self::ICON_BASE_URL . self::ICON_LARGE_PATH,
            'medium' => self::ICON_BASE_URL . self::ICON_MEDIUM_PATH,
            'vector' => self::ICON_BASE_URL . self::ICON_VECTOR_PATH,
        ];
    }

    /**
     * Get label configuration
     *
     * @return null
     */
    private static function getLabelConfig()
    {
        return null;
    }

    /**
     * Get manual capture configuration
     *
     * @return array
     */
    private static function getManualCaptureConfig(): array
    {
        return [
            'is_enabled' => false,
            'supported' => false,
        ];
    }

    /**
     * Get preferred countries configuration
     *
     * @return array
     */
    private static function getPreferredCountriesConfig(): array
    {
        return [];
    }

    /**
     * Get required customer data configuration
     *
     * @return array
     */
    private static function getRequiredCustomerDataConfig(): array
    {
        return [];
    }

    /**
     * Get tokenization configuration with all supported models
     *
     * @return array
     */
    private static function getTokenizationConfig(): array
    {
        return [
            'is_enabled' => false,
            'models' => self::getTokenizationModelsConfig(),
        ];
    }

    /**
     * Get tokenization models configuration
     *
     * @return array
     */
    private static function getTokenizationModelsConfig(): array
    {
        return [
            'cardonfile' => false,
            'subscription' => false,
            'unscheduled' => false,
        ];
    }
}
