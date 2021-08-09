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

namespace MultiSafepay\PrestaShop\Services;

use MultiSafepay\Exception\InvalidApiKeyException;
use MultiSafepay\Sdk;
use Buzz\Client\Curl;
use Nyholm\Psr7\Factory\Psr17Factory;
use Configuration;

/**
 * This class returns the SDK object.
 *
 * @since      4.0.0
 */
class SdkService
{

    /**
     * @var     string      The API Key
     */
    private $apiKey;

    /**
     * @var   boolean       The environment.
     */
    private $testMode;

    /**
     * @var   Sdk       Sdk.
     */
    private $sdk = null;


    /**
     * SdkService constructor.
     *
     * @param  string  $apiKey
     * @param  boolean $testMode
     */
    public function __construct(string $apiKey = null, bool $testMode = null)
    {
        $this->apiKey   = $apiKey ?? $this->getApiKey();
        $this->testMode = $testMode ?? $this->getTestMode();
        $psrFactory     = new Psr17Factory();
        $client         = new Curl($psrFactory);
        try {
            $this->sdk = new Sdk($this->apiKey, ( $this->testMode ) ? false : true, $client, $psrFactory, $psrFactory);
        } catch (InvalidApiKeyException $invalidApiKeyException) {
            // log
        }
    }

    /**
     * Returns if test mode is enable
     *
     * @return  boolean
     */
    public function getTestMode(): bool
    {
        return (bool) Configuration::get('MULTISAFEPAY_TEST_MODE');
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
            return Configuration::get('MULTISAFEPAY_TEST_API_KEY');
        }
        return Configuration::get('MULTISAFEPAY_TEST_API_KEY');
    }

    /**
     * @return Sdk
     */
    public function getSdk(): Sdk
    {
        return $this->sdk;
    }
}
