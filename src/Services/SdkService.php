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
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\PrestaShop\Services;

use MultiSafepay\Exception\InvalidApiKeyException;
use MultiSafepay\Sdk;
use Buzz\Client\Curl;
use Nyholm\Psr7\Factory\Psr17Factory;
use Configuration;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;

/**
 * Class SdkService
 * @package MultiSafepay\PrestaShop\Services
 */
class SdkService
{

    /**
     * @var   Sdk       Sdk.
     */
    private $sdk;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * SdkService constructor.
     * @param Configuration $configuration
     */
    public function __construct($configuration = null)
    {
        if (is_null($configuration)) {
            $this->configuration = new Configuration();
        } else {
            $this->configuration = $configuration;
        }
    }

    /**
     * Returns if test mode is enable
     *
     * @return  boolean
     */
    public function getTestMode(): bool
    {
        return (bool)$this->configuration::get('MULTISAFEPAY_OFFICIAL_TEST_MODE');
    }

    /**
     * Returns api key set in settings page according with
     * the environment selected
     *
     * @return  string
     */
    public function getApiKey(): string
    {
        if ($this->getTestMode()) {
            return (string)$this->configuration::get('MULTISAFEPAY_OFFICIAL_TEST_API_KEY');
        }
        return (string)$this->configuration::get('MULTISAFEPAY_OFFICIAL_API_KEY');
    }

    /**
     * @return Sdk
     */
    public function getSdk()
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
            LoggerHelper::logError($invalidApiKeyException->getMessage());
        }
    }
}
