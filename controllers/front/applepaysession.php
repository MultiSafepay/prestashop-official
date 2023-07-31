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

use MultiSafepay\Api\Wallets\ApplePay\MerchantSessionRequest;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Services\SdkService;
use Psr\Http\Client\ClientExceptionInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class MultisafepayOfficialApplepaysessionModuleFrontController extends ModuleFrontController
{

    public const VALIDATION_URL_KEY = 'validation_url';
    public const ORIGIN_DOMAIN_KEY = 'origin_domain';

    public function __construct()
    {
        parent::__construct();
        $this->ajax = true;
    }

    /**
     * @return void
     */
    public function initContent(): void
    {
        // Call the parent init content method
        parent::initContent();

        // Set the content type to json
        header('Content-Type: application/json');

        $appleSessionArguments = $this->getApplePaySessionArguments();

        try {
            echo $this->getApplePayMerchantSession($appleSessionArguments);
        } catch (ApiException|Exception|ClientExceptionInterface $exception) {
            $errorMessage = 'Error when trying to get the ApplePay session via MultiSafepay SDK';
            LoggerHelper::logAlert($errorMessage . ': ' . $exception->getMessage());
            echo json_encode(['message' => $errorMessage]);
        }

        exit;
    }

    /**
     * Validate the required input and return the values
     *
     * @return array
     */
    private function getApplePaySessionArguments(): array
    {
        $validationUrl = Tools::getValue(self::VALIDATION_URL_KEY);
        $originDomain = Tools::getValue(self::ORIGIN_DOMAIN_KEY);

        if (empty($validationUrl)) {
            LoggerHelper::logError('Error when trying to get the ApplePay session. Validation URL empty');
            exit;
        }

        if (empty($originDomain)) {
            LoggerHelper::logError('Error when trying to get the ApplePay session. Origin domain empty');
            exit;
        }

        return [
            self::VALIDATION_URL_KEY => $validationUrl,
            self::ORIGIN_DOMAIN_KEY => $originDomain
        ];
    }

    /**
     * Return the Apple Pay Merchant session
     *
     * @param array $appleSessionArguments
     * @return string
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    private function getApplePayMerchantSession(array $appleSessionArguments): string
    {
        /** @var SdkService $sdkService */
        $sdkService = $this->module->get('multisafepay.sdk_service');
        $wallerManager = $sdkService->getSdk()->getWalletManager();
        $applePayMerchantSessionRequest = $this->getMerchantSessionRequest($appleSessionArguments);

        return $wallerManager->createApplePayMerchantSession(
            $applePayMerchantSessionRequest
        )->getMerchantSession();
    }

    /**
     * Return the MerchantSessionRequest object
     *
     * @param array $appleSessionArguments
     * @return MerchantSessionRequest
     */
    private function getMerchantSessionRequest(array $appleSessionArguments): MerchantSessionRequest
    {
        return (new MerchantSessionRequest())
            ->addValidationUrl($appleSessionArguments[self::VALIDATION_URL_KEY])
            ->addOriginDomain($appleSessionArguments[self::ORIGIN_DOMAIN_KEY]);
    }
}
