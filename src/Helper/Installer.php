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

namespace MultiSafepay\PrestaShop\Helper;

use Configuration;
use Country;
use Hook;
use Language;
use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultisafepayOfficial;
use OrderState;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tab;
use Tools;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class Installer
 */
class Installer
{
    /**
     * @var MultisafepayOfficial
     */
    private $module;

    /**
     * Uninstaller constructor.
     *
     * @param MultisafepayOfficial $module
     */
    public function __construct(MultisafepayOfficial $module)
    {
        $this->module = $module;
    }

    /**
     * Call this function when installing the MultiSafepay module
     * @return void
     */
    public function install(): void
    {
        $this->registerMultiSafepayOrderStatuses();
        $this->installMultiSafepayTab();
        $this->registerOrderViewHook();
        $this->setDefaultValues();
    }

    /**
     * Install capture resources without resetting merchant settings during upgrades.
     *
     * @return void
     * @throws PrestaShopException
     */
    public function installManualCaptureResources(): void
    {
        if (!$this->module->id) {
            throw new PrestaShopException('Cannot install manual capture resources for an uninstalled module.');
        }

        $this->registerMultiSafepayOrderStatuses(['authorized', 'partial_captured']);
        $this->installCaptureTab();
        $this->registerOrderViewHook();
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    private function registerOrderViewHook(): void
    {
        $hookName = version_compare(_PS_VERSION_, '1.7.7.0', '>=')
            ? 'actionGetAdminOrderButtons'
            : 'displayBackOfficeOrderActions';

        if (!Hook::getIdByName($hookName)) {
            $hook = new Hook();
            $hook->name = $hookName;
            $hook->title = $hookName;
            $hook->description = $hookName;
            if (!$hook->add()) {
                throw new PrestaShopException('Failed to create manual capture hook "' . $hookName . '".');
            }
        }

        // PrestaShop skips existing registrations and handles all shops when no shop list is supplied.
        if (!$this->module->registerHook($hookName)) {
            throw new PrestaShopException('Failed to register manual capture hook "' . $hookName . '".');
        }
    }

    /**
     * Install the MultiSafepay tab
     * @return void
     */
    private function installMultiSafepayTab(): void
    {
        $idParent = (new SettingsBuilder($this->module))->getAdminTab('IMPROVE');

        $tab             = new Tab();
        $tab->class_name = 'AdminMultisafepayOfficial';
        $tab->id_parent  = $idParent;
        $tab->module     = 'multisafepayofficial';
        $tab->active     = true;
        $tab->icon       = 'multisafepay icon-multisafepay';
        $languages       = Language::getLanguages(true);
        foreach ($languages as $language) {
            $tab->name[$language['id_lang']] = 'MultiSafepay';
        }
        if (!$tab->add()) {
            throw new PrestaShopException('Failed to install the MultiSafepay admin tab.');
        }

        // Install hidden tab for capture controller
        $this->installCaptureTab();
    }

    /**
     * Install the hidden capture controller tab
     *
     * This tab is hidden from the menu but allows the controller to be accessed
     * via the admin link for manual capture functionality.
     *
     * @return void
     */
    private function installCaptureTab(): void
    {
        $tabId = Tab::getIdFromClassName('AdminMultisafepayOfficialCapture');
        if ($tabId) {
            $tab = new Tab($tabId);
            if ($tab->module !== $this->module->name) {
                throw new PrestaShopException('The manual capture admin tab belongs to another module.');
            }
            return;
        }

        $captureTab             = new Tab();
        $captureTab->class_name = 'AdminMultisafepayOfficialCapture';
        $captureTab->id_parent  = -1; // Hidden tab (not visible in menu)
        $captureTab->module     = 'multisafepayofficial';
        $captureTab->active     = true;
        $languages              = Language::getLanguages(true);
        foreach ($languages as $language) {
            $captureTab->name[$language['id_lang']] = 'MultiSafepay Capture';
        }
        if (!$captureTab->add() || (int)$captureTab->id <= 0) {
            throw new PrestaShopException('Failed to install the MultiSafepay manual capture admin tab.');
        }
    }

    /**
     * Set default values on install
     *
     * @throws PrestaShopException
     * @return void
     */
    private function setDefaultValues(): void
    {
        foreach (SettingsBuilder::getConfigFieldsAndDefaultValues() as $configField => $configData) {
            $this->updateGlobalValueOrFail($configField, $configData['default']);
        }

        $paymentOptionService = new PaymentOptionService($this->module);
        foreach ($paymentOptionService->getMultiSafepayPaymentOptions() as $paymentOption) {
            foreach ($paymentOption->getGatewaySettings() as $settingKey => $settings) {
                $this->updateGlobalValueOrFail($settingKey, $settings['default']);
            }
            // Adding default values for countries of the branded payment methods
            $brandedCountries = $paymentOption->getAllowedCountries();
            if (!empty($brandedCountries)) {
                $isoBrandedCountries = [];
                foreach ($brandedCountries as $brandedCountry) {
                    $isoBrandedCountries[] = (string)Country::getByIso($brandedCountry);
                }
                $this->updateGlobalValueOrFail(
                    'MULTISAFEPAY_OFFICIAL_COUNTRIES_' . $paymentOption->getUniqueName(),
                    json_encode($isoBrandedCountries)
                );
            }
        }
    }

    /**
     * Register the MultiSafepay Order statuses
     *
     * @param string[] $statusKeys Empty to register all states.
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function registerMultiSafepayOrderStatuses(array $statusKeys = []): void
    {
        $multisafepayStatusPrefix = 'MULTISAFEPAY_OFFICIAL_OS_';
        $multisafepayOrderStatuses = $this->getMultiSafepayOrderStatuses();
        foreach ($multisafepayOrderStatuses as $multisafepayOrderStatusKey => $multisafepayOrderStatusValues) {
            if ($statusKeys && !in_array($multisafepayOrderStatusKey, $statusKeys, true)) {
                continue;
            }

            $statusConfigKey = $multisafepayStatusPrefix . Tools::strtoupper($multisafepayOrderStatusKey);
            $configValue = trim((string)Configuration::getGlobalValue($statusConfigKey));
            $orderStateId = ctype_digit($configValue) ? (int)$configValue : 0;
            $orderState = $orderStateId > 0 ? new OrderState($orderStateId) : null;

            if (!$orderState || !Validate::isLoadedObject($orderState) || $orderState->deleted) {
                $orderState = $this->createOrderStatus($multisafepayOrderStatusValues);
                $orderStateId = (int)$orderState->id;
                $this->updateGlobalValueOrFail(
                    $statusConfigKey,
                    $orderStateId
                );
            }

            if ($multisafepayOrderStatusKey === 'partial_captured'
                && $orderState->color !== $multisafepayOrderStatusValues['color']
            ) {
                $orderState->color = $multisafepayOrderStatusValues['color'];
                if (!$orderState->update()) {
                    throw new PrestaShopException('Failed to update the MultiSafepay partially captured order status.');
                }
            }

            $this->cloneOrderStateIcon($orderStateId, $multisafepayOrderStatusValues);

            if ($multisafepayOrderStatusKey === 'authorized') {
                $this->updateGlobalValueOrFail($multisafepayStatusPrefix . 'AUTHORIZED_STATUS_CREATED', '1');
            }
            if ($multisafepayOrderStatusKey === 'partial_captured') {
                $this->updateGlobalValueOrFail($multisafepayStatusPrefix . 'PARTIAL_CAPTURED_STATUS_CREATED', '1');
            }
        }
    }

    /**
     * Persist a configuration value during install and fail fast if DB persistence fails.
     *
     * @param string $configurationKey
     * @param mixed $configurationValue
     * @return void
     * @throws PrestaShopException
     */
    private function updateGlobalValueOrFail(string $configurationKey, $configurationValue): void
    {
        if (!Configuration::updateGlobalValue($configurationKey, $configurationValue)) {
            throw new PrestaShopException(
                sprintf(
                    'Failed to persist MultiSafepay configuration key "%s" during install or upgrade.',
                    $configurationKey
                )
            );
        }
    }

    /**
     * Creates the Order Statuses
     *
     * @param array $multisafepayOrderStatusValues
     * @return OrderState
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function createOrderStatus(array $multisafepayOrderStatusValues): OrderState
    {
        $orderState = new OrderState();
        foreach (Language::getLanguages() as $language) {
            $orderState->name[$language['id_lang']] = 'MultiSafepay ' . $multisafepayOrderStatusValues['name'];
        }
        $orderState->send_email  = $multisafepayOrderStatusValues['send_mail'];
        $orderState->color       = $multisafepayOrderStatusValues['color'];
        $orderState->unremovable = false;
        $orderState->hidden      = false;
        $orderState->delivery    = false;
        $orderState->logable     = $multisafepayOrderStatusValues['logable'];
        $orderState->invoice     = $multisafepayOrderStatusValues['invoice'];
        $orderState->template    = $multisafepayOrderStatusValues['template'];
        $orderState->paid        = $multisafepayOrderStatusValues['paid'];
        $orderState->module_name = 'multisafepayofficial';
        if (!$orderState->add() || (int)$orderState->id <= 0) {
            throw new PrestaShopException(
                sprintf(
                    'Failed to create MultiSafepay order status "%s".',
                    (string)($multisafepayOrderStatusValues['name'] ?? 'unknown')
                )
            );
        }

        return $orderState;
    }

    /**
     * Clone an icon from module assets to PrestaShop order state icon directory.
     *
     * Best-effort and non-blocking by design: if no icon is found
     * or destination is not writable, installation continues.
     * Failures may be logged as warnings, but they do not interrupt install.
     *
     * @param int $orderStateId
     * @param array $multisafepayOrderStatusValues
     * @return void
     */
    private function cloneOrderStateIcon(int $orderStateId, array $multisafepayOrderStatusValues): void
    {
        if ($orderStateId <= 0) {
            return;
        }

        $sourceDirectory = _PS_MODULE_DIR_ . 'multisafepayofficial/views/img/order_states/';
        $destinationDirectory = defined('_PS_ORDER_STATE_IMG_DIR_')
            ? _PS_ORDER_STATE_IMG_DIR_
            : _PS_IMG_DIR_ . 'os/';
        $destinationDirectory = rtrim(
            $destinationDirectory,
            '/\\'
        ) . DIRECTORY_SEPARATOR;

        if (!is_dir($destinationDirectory) || !is_writable($destinationDirectory)) {
            return;
        }

        $destinationPath = $destinationDirectory . $orderStateId . '.gif';
        if (is_file($destinationPath)) {
            return;
        }

        $candidates = array_unique([
            basename((string)($multisafepayOrderStatusValues['icon'] ?? 'default.gif')),
            'default.gif',
        ]);

        $lastSourcePathTried = '';

        if (is_dir($sourceDirectory)) {
            foreach ($candidates as $candidate) {
                $candidate = (string)$candidate;
                if ($candidate === '') {
                    continue;
                }

                if (pathinfo($candidate, PATHINFO_EXTENSION) === '') {
                    $candidate .= '.gif';
                }

                $sourcePath = $sourceDirectory . $candidate;
                if (!is_file($sourcePath) || !is_readable($sourcePath)) {
                    continue;
                }

                $lastSourcePathTried = $sourcePath;

                if (@copy($sourcePath, $destinationPath)) {
                    return;
                }
            }
        }

        $fallbackSourcePath = $destinationDirectory . '2.gif';
        if (is_file($fallbackSourcePath) && is_readable($fallbackSourcePath)) {
            $lastSourcePathTried = $fallbackSourcePath;
            if (@copy($fallbackSourcePath, $destinationPath)) {
                return;
            }
        }

        if ($lastSourcePathTried !== '') {
            LoggerHelper::log(
                'warning',
                sprintf(
                    'Failed to copy MultiSafepay order-state icon from "%s" to "%s".',
                    $lastSourcePathTried,
                    $destinationPath
                )
            );
            return;
        }

        LoggerHelper::log(
            'warning',
            sprintf(
                'Could not resolve a readable MultiSafepay order-state icon source for destination "%s". '
                . 'Checked module directory "%s" and fallback "%s".',
                $destinationPath,
                $sourceDirectory,
                $fallbackSourcePath
            )
        );
    }

    /**
     * Return an array with MultiSafepay order statuses
     *
     * @return array
     */
    public function getMultiSafepayOrderStatuses(): array
    {
        return [
            'authorized' => [
                'name'      => 'authorized',
                'icon'      => 'authorized.gif',
                'send_mail' => false,
                'color'     => '#207F4B',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
            'partial_captured' => [
                'name'      => 'partially captured',
                'icon'      => 'partially_captured.gif',
                'send_mail' => false,
                'color'     => '#A700D3',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
            'chargeback' => [
                'name'      => 'chargeback',
                'icon'      => 'chargeback.gif',
                'send_mail' => true,
                'color'     => '#EC2E15',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
            'initialized' => [
                'name'      => 'initialized',
                'icon'      => 'initialized.gif',
                'send_mail' => false,
                'color'     => '#4169E1',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
            'partial_refunded' => [
                'name'      => 'partial refunded',
                'icon'      => 'partial_refunded.gif',
                'send_mail' => true,
                'color'     => '#EC2E15',
                'invoice'   => false,
                'template'  => 'refund',
                'paid'      => false,
                'logable'   => false
            ],
            'uncleared' => [
                'name'      => 'uncleared',
                'icon'      => 'uncleared.gif',
                'send_mail' => false,
                'color'     => '#EC2E15',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
        ];
    }
}
