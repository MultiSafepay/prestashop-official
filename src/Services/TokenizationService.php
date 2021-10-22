<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\Services;

use MultisafepayOfficial;
use MultiSafepay\Api\Tokens\Token;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use Context;

/**
 * Class TokenizationService
 * @package MultiSafepay\PrestaShop\Services
 */
class TokenizationService
{
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

        // The API will raise an error if there are no tokens for a customer, therefore we catch the error and return an empty array
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

        // The API will raise an error if there are no tokens for a customer, therefore we catch the error and return an empty array
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
            LoggerHelper::logError('There was an error when deleting a token: ' . $exception->getMessage());
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
                'name'    => $this->module->l('Use new payment details'),
                'value'   => 'new',
            ];

            $inputFields[] = [
                'type'        => 'radio',
                'name'        => 'selectedToken',
                'class'       => 'form-group-token-list',
                'options'     => $options,
                'placeholder' => $this->module->l('Payment details'),
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
                'label' => $this->module->l('Save payment details for future purchases.'),
            ]
        ];
    }

    /**
     * @return array
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getTokensForCustomerAccount(): array
    {
        /** @var PaymentOptionService $paymentOptionService */
        $paymentOptionService = $this->module->get('multisafepay.payment_option_service');

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
        if (is_null($expiryDate) || strlen((string)$expiryDate) !== 4) {
            return '--';
        }

        $parts = str_split((string)$expiryDate, 2);
        return $parts[1] . '/' . $parts[0];
    }
}
