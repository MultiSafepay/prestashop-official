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
use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use OrderState;
use PrestaShopCollection;
use Tab;
use MultisafepayOfficial;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class Uninstaller
 */
class Uninstaller
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
     * Call this function when uninstalling the MultiSafepay module
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function uninstall(): void
    {
        $this->uninstallMultiSafepayTab();
        $this->deleteConfigValues();
        $this->removeOrderStatuses();
    }

    /**
     * @return void
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    protected function uninstallMultiSafepayTab(): void
    {
        $tabId = (new SettingsBuilder($this->module))->getAdminTab('AdminMultisafepayOfficial');

        $tab   = new Tab($tabId);
        $tab->delete();
    }

    /**
     * Delete all saved config values
     * @return void
     */
    protected function deleteConfigValues(): void
    {
        $configValues = (new SettingsBuilder($this->module))->getConfigFormValues();
        foreach (array_keys($configValues) as $configValue) {
            Configuration::deleteByName((string)$configValue);
        }

        LoggerHelper::log(
            'info',
            'Module config values has been removed',
            true
        );
    }

    /**
     * @throws \PrestaShopException
     */
    protected function removeOrderStatuses(): void
    {
        /** @var OrderState[] $orderStatuses */
        $orderStatuses = (new PrestaShopCollection('OrderState'))->where(
            'module_name',
            '=',
            $this->module->name
        )->getResults();

        if (!empty($orderStatuses)) {
            foreach ($orderStatuses as $orderStatus) {
                $this->orderStateSoftDelete($orderStatus);
            }
        }
    }

    /**
     * @param OrderState $orderStatus
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function orderStateSoftDelete(OrderState $orderStatus): void
    {
        $orderStatus->deleted = true;
        $orderStatus->update();
    }
}
