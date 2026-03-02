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
use Language;
use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultisafepayOfficial;
use OrderState;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tab;
use Tools;

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
        $this->setDefaultValues();
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
        $tab->add();

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
        $captureTab             = new Tab();
        $captureTab->class_name = 'AdminMultisafepayOfficialCapture';
        $captureTab->id_parent  = -1; // Hidden tab (not visible in menu)
        $captureTab->module     = 'multisafepayofficial';
        $captureTab->active     = true;
        $languages              = Language::getLanguages(true);
        foreach ($languages as $language) {
            $captureTab->name[$language['id_lang']] = 'MultiSafepay Capture';
        }
        $captureTab->add();
    }

    /**
     * Set default values on install
     * @return void
     */
    private function setDefaultValues(): void
    {
        foreach (SettingsBuilder::getConfigFieldsAndDefaultValues() as $configField => $configData) {
            Configuration::updateGlobalValue($configField, $configData['default']);
        }

        $paymentOptionService = new PaymentOptionService($this->module);
        foreach ($paymentOptionService->getMultiSafepayPaymentOptions() as $paymentOption) {
            foreach ($paymentOption->getGatewaySettings() as $settingKey => $settings) {
                Configuration::updateGlobalValue($settingKey, $settings['default']);
            }
            // Adding default values for countries of the branded payment methods
            $brandedCountries = $paymentOption->getAllowedCountries();
            if (!empty($brandedCountries)) {
                $isoBrandedCountries = [];
                foreach ($brandedCountries as $brandedCountry) {
                    $isoBrandedCountries[] = (string)Country::getByIso($brandedCountry);
                }
                Configuration::updateGlobalValue('MULTISAFEPAY_OFFICIAL_COUNTRIES_' .
                    $paymentOption->getUniqueName(), json_encode($isoBrandedCountries));
            }
        }
    }

    /**
     * Register the MultiSafepay Order statuses
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function registerMultiSafepayOrderStatuses(): void
    {
        $multisafepayStatusPrefix = 'MULTISAFEPAY_OFFICIAL_OS_';
        $multisafepayOrderStatuses = $this->getMultiSafepayOrderStatuses();
        foreach ($multisafepayOrderStatuses as $multisafepayOrderStatusKey => $multisafepayOrderStatusValues) {
            if (!Configuration::get($multisafepayStatusPrefix . Tools::strtoupper($multisafepayOrderStatusKey))) {
                $orderState = $this->createOrderStatus($multisafepayOrderStatusValues);
                Configuration::updateGlobalValue(
                    $multisafepayStatusPrefix . Tools::strtoupper($multisafepayOrderStatusKey),
                    (int)$orderState->id
                );
            }
            if ($multisafepayOrderStatusKey === 'authorized') {
                Configuration::updateGlobalValue($multisafepayStatusPrefix . 'AUTHORIZED_STATUS_CREATED', '1');
            }
            if ($multisafepayOrderStatusKey === 'partial_captured') {
                Configuration::updateGlobalValue($multisafepayStatusPrefix . 'PARTIAL_CAPTURED_STATUS_CREATED', '1');
            }
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
        $orderState->add();
        return $orderState;
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
                'send_mail' => false,
                'color'     => '#207F4B',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
            'partial_captured' => [
                'name'      => 'partially captured',
                'send_mail' => false,
                'color'     => '#A700D3',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
            'chargeback' => [
                'name'      => 'chargeback',
                'send_mail' => true,
                'color'     => '#EC2E15',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
            'initialized' => [
                'name'      => 'initialized',
                'send_mail' => false,
                'color'     => '#4169E1',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
            'partial_refunded' => [
                'name'      => 'partial refunded',
                'send_mail' => true,
                'color'     => '#EC2E15',
                'invoice'   => false,
                'template'  => 'refund',
                'paid'      => false,
                'logable'   => false
            ],
            'uncleared' => [
                'name'      => 'uncleared',
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
