<?php declare(strict_types=1);

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
     * Returns if test mode is enable
     *
     * @return  boolean
     */
    public function getTestMode(): bool
    {
        return (bool)Configuration::get('MULTISAFEPAY_TEST_MODE');
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
            return (string)Configuration::get('MULTISAFEPAY_TEST_API_KEY');
        }
        return (string)Configuration::get('MULTISAFEPAY_TEST_API_KEY');
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
