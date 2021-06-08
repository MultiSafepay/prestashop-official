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
class SdkService {

    /**
     * @var     string      The API Key
     */
    private $api_key;

    /**
     * @var   boolean       The environment.
     */
    private $test_mode;

    /**
     * @var   Sdk       Sdk.
     */
    private $sdk = null;


    /**
     * SdkService constructor.
     *
     * @param  string  $api_key
     * @param  boolean $test_mode
     */
    public function __construct( string $api_key = null, bool $test_mode = null ) {
        $this->api_key   = $api_key ?? $this->getApiKey();
        $this->test_mode = $test_mode ?? $this->getTestMode();
        $psr_factory     = new Psr17Factory();
        $client          = new Curl( $psr_factory );
        try {
            $this->sdk = new Sdk( $this->api_key, ( $this->test_mode ) ? false : true, $client, $psr_factory, $psr_factory );
        } catch ( InvalidApiKeyException $invalid_api_key_exception ) {
            // log
        }
    }

    /**
     * Returns if test mode is enable
     *
     * @return  boolean
     */
    public function getTestMode(): bool {
        return (bool) Configuration::get('MULTISAFEPAY_TEST_MODE');
    }

    /**
     * Returns api key set in settings page according with
     * the environment selected
     *
     * @return  string
     */
    public function getApiKey(): string {
        if ( $this->getTestMode() ) {
            return Configuration::get('MULTISAFEPAY_TEST_API_KEY');
        }
        return Configuration::get('MULTISAFEPAY_TEST_API_KEY');
    }

    /**
     * @return Sdk
     */
    public function getSdk(): Sdk {
        return $this->sdk;
    }
}
