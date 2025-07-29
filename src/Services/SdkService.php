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

use Context;
use MultiSafepay\Exception\InvalidApiKeyException;
use MultiSafepay\Sdk;
use Buzz\Client\Curl;
use Nyholm\Psr7\Factory\Psr17Factory;
use Configuration;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class SdkService
 * @package MultiSafepay\PrestaShop\Services
 */
class SdkService
{

    /**
     * @var Sdk|null
     */
    private $sdk = null;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * SdkService constructor.
     *
     * @param Configuration|null  $configuration
     */
    public function __construct(?Configuration $configuration = null)
    {
        if (is_null($configuration)) {
            $this->configuration = new Configuration();
        } else {
            $this->configuration = $configuration;
        }
    }

    /**
     * Returns if test mode is enabled
     *
     * @return  boolean
     */
    public function getTestMode(): bool
    {
        if (defined('_PS_ADMIN_DIR_')) {
            return isset($_POST['MULTISAFEPAY_OFFICIAL_TEST_MODE'])
                ? (bool)Tools::getValue('MULTISAFEPAY_OFFICIAL_TEST_MODE')
                : (bool)$this->configuration::get('MULTISAFEPAY_OFFICIAL_TEST_MODE');
        }
        return (bool)$this->configuration::get('MULTISAFEPAY_OFFICIAL_TEST_MODE');
    }

    /**
     * Returns api key set in settings page according to
     * the environment selected
     *
     * @return  string
     */
    public function getApiKey(): string
    {
        if (defined('_PS_ADMIN_DIR_')) {
            if ($this->getTestMode()) {
                return isset($_POST['MULTISAFEPAY_OFFICIAL_TEST_API_KEY'])
                    ? (string)Tools::getValue('MULTISAFEPAY_OFFICIAL_TEST_API_KEY')
                    : (string)$this->configuration::get('MULTISAFEPAY_OFFICIAL_TEST_API_KEY');
            }
            return isset($_POST['MULTISAFEPAY_OFFICIAL_API_KEY'])
                ? (string)Tools::getValue('MULTISAFEPAY_OFFICIAL_API_KEY')
                : (string)$this->configuration::get('MULTISAFEPAY_OFFICIAL_API_KEY');
        }

        if ($this->getTestMode()) {
            return (string)$this->configuration::get('MULTISAFEPAY_OFFICIAL_TEST_API_KEY');
        }
        return (string)$this->configuration::get('MULTISAFEPAY_OFFICIAL_API_KEY');
    }

    /**
     * @return Sdk|null
     */
    public function getSdk(): ?Sdk
    {
        if (!isset($this->sdk)) {
            $this->initSdk();
        }
        return $this->sdk;
    }

    /**
     * Initiate the sdk
     */
    private function initSdk(): void
    {
        $psrFactory = new Psr17Factory();
        $client     = new Curl($psrFactory);
        try {
            $this->sdk = new Sdk(
                $this->getApiKey(),
                !$this->getTestMode(),
                $client,
                $psrFactory,
                $psrFactory
            );
        } catch (InvalidApiKeyException $invalidApiKeyException) {
            LoggerHelper::logException(
                'error',
                $invalidApiKeyException,
                '',
                null,
                Context::getContext()->cart->id ?? null
            );
        }
    }
}
