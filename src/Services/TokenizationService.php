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

use MultisafepayOfficial;
use MultiSafepay\Api\Tokens\Token;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use Context;
use Tools;

/**
 * Class TokenizationService
 * @package MultiSafepay\PrestaShop\Services
 */
class TokenizationService
{
    public const CLASS_NAME = 'TokenizationService';

    /**
     * @var MultisafepayOfficial
     */
    protected $module;

    /**
     * @var SdkService
     */
    protected $sdkService;

    /**
     * TokenizationService constructor.
     * @param MultisafepayOfficial $module
     * @param SdkService $sdkService
     */
    public function __construct(MultisafepayOfficial $module, SdkService $sdkService)
    {
        $this->module     = $module;
        $this->sdkService = $sdkService;
    }

    /**
     * @param string $customerId
     * @param string $gatewayCode
     *
     * @return Token[]
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getTokensByCustomerIdAndGatewayCode(string $customerId, string $gatewayCode): array
    {
        $tokenManager = $this->sdkService->getSdk()->getTokenManager();

        // The API will raise an error if there are no tokens for a customer, therefore we return an empty array
        try {
            return $tokenManager->getListByGatewayCode($customerId, $gatewayCode);
        } catch (ApiException $exception) {
            return [];
        }
    }

    /**
     * @param string $customerId
     *
     * @return Token[]
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getTokensByCustomerId(string $customerId): array
    {
        $tokenManager = $this->sdkService->getSdk()->getTokenManager();

        // The API will raise an error if there are no tokens for a customer, therefore we return an empty array
        try {
            return $tokenManager->getList($customerId);
        } catch (ApiException $exception) {
            return [];
        }
    }

    /**
     * @param string $customerId
     * @param string $tokenId
     * @return bool
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function deleteToken(string $customerId, string $tokenId): bool
    {
        $tokenManager = $this->sdkService->getSdk()->getTokenManager();
        try {
            return $tokenManager->delete($tokenId, $customerId);
        } catch (ApiException $exception) {
            LoggerHelper::logException(
                'error',
                $exception,
                'There was an error when deleting a token',
                null,
                Context::getContext()->cart->id ?? null
            );
            return false;
        }
    }

    /**
     * @param string $customerId
     * @param BasePaymentOption $paymentOption
     *
     * @return array
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function createTokenizationCheckoutFields(string $customerId, BasePaymentOption $paymentOption): array
    {
        $inputFields = [];

        $tokens = $this->getTokensByCustomerIdAndGatewayCode($customerId, $paymentOption->getGatewayCode());

        if (!empty($tokens)) {
            $options = [];

            foreach ($tokens as $token) {
                $options[] = [
                    'name'  => $token->getDisplay(),
                    'value' => $token->getToken(),
                ];
            }

            $options[] = [
                'name'    => $this->module->l('Use new payment details', self::CLASS_NAME),
                'value'   => 'new',
            ];

            $inputFields[] = [
                'type'        => 'radio',
                'name'        => 'selectedToken',
                'class'       => 'form-group-token-list',
                'options'     => $options,
                'placeholder' => $this->module->l('Payment details', self::CLASS_NAME),
            ];
        }

        return $inputFields;
    }


    public function createTokenizationSavePaymentDetailsCheckbox(): array
    {
        return [
            [
                'type'  => 'checkbox',
                'name'  => 'saveToken',
                'label' => $this->module->l('Save payment details for future purchases.', self::CLASS_NAME),
            ]
        ];
    }

    /**
     * @return array
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getTokensForCustomerAccount(): array
    {
        $paymentOptionService = new PaymentOptionService($this->module);

        $tokens = $this->getTokensByCustomerId((string)Context::getContext()->customer->id);

        $customerTokens = [];
        foreach ($tokens as $token) {
            $paymentOption = $paymentOptionService->getMultiSafepayPaymentOption($token->getGatewayCode());
            $customerTokens[] = [
                'tokenId'           => $token->getToken(),
                'display'           => $token->getDisplay(),
                'expiryDate'        => $this->formatExpiryDate($token->getExpiryDate()),
                'paymentOptionName' => $paymentOption->getName(),
            ];
        }

        return $customerTokens;
    }

    /**
     * @param mixed $expiryDate
     * @return string
     */
    private function formatExpiryDate($expiryDate): string
    {
        if (is_null($expiryDate) || Tools::strlen((string)$expiryDate) !== 4) {
            return '--';
        }

        $parts = str_split((string)$expiryDate, 2);
        return $parts[1] . '/' . $parts[0];
    }
}
