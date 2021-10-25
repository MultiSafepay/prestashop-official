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
