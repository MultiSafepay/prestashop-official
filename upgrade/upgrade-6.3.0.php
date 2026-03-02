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

use MultiSafepay\PrestaShop\Helper\LoggerHelper;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Ensures required MultiSafepay order states exist during upgrade to 6.2.0.
 *
 * @return bool True, when both states are ensured successfully, false otherwise.
 */
function upgrade_module_6_3_0(): bool
{
    try {
        $authorizedKey = 'MULTISAFEPAY_OFFICIAL_OS_AUTHORIZED';
        $authorizedCreatedKey = 'MULTISAFEPAY_OFFICIAL_OS_AUTHORIZED_STATUS_CREATED';
        $partialCapturedKey = 'MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED';
        $partialCapturedCreatedKey = 'MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED_STATUS_CREATED';

        $authorizedCreated = upgradeCreateOrderStateIfMissing(
            $authorizedKey,
            $authorizedCreatedKey,
            [
                'name'      => 'authorized',
                'send_mail' => false,
                'color'     => '#207F4B',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false,
            ]
        );

        if (!$authorizedCreated) {
            LoggerHelper::log(
                'error',
                sprintf(
                    'Upgrade 6.2.0 aborted: failed to ensure order state %s.',
                    $authorizedKey
                )
            );

            return false;
        }

        $partialCapturedCreated = upgradeCreateOrderStateIfMissing(
            $partialCapturedKey,
            $partialCapturedCreatedKey,
            [
                'name'      => 'partially captured',
                'send_mail' => false,
                'color'     => '#A700D3',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false,
            ]
        );

        if (!$partialCapturedCreated) {
            LoggerHelper::log(
                'error',
                sprintf(
                    'Upgrade 6.2.0 aborted: failed to ensure order state %s.',
                    $partialCapturedKey
                )
            );

            return false;
        }

        return true;
    } catch (PrestaShopDatabaseException $exception) {
        LoggerHelper::log(
            'error',
            sprintf(
                'Upgrade 6.2.0 aborted due to database exception: %s',
                $exception->getMessage()
            )
        );

        return false;
    } catch (PrestaShopException $exception) {
        LoggerHelper::log(
            'error',
            sprintf(
                'Upgrade 6.2.0 aborted due to PrestaShop exception: %s',
                $exception->getMessage()
            )
        );

        return false;
    }
}

/**
 * @param string $statusConfigKey
 * @param string $statusReadyFlagKey
 * @param array<string, mixed> $orderStatusData
 *
 * @return bool
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function upgradeCreateOrderStateIfMissing(
    string $statusConfigKey,
    string $statusReadyFlagKey,
    array $orderStatusData
): bool {
    if (!Configuration::get($statusConfigKey)) {
        $orderState = new OrderState();
        foreach (Language::getLanguages() as $language) {
            $orderState->name[$language['id_lang']] = 'MultiSafepay ' . $orderStatusData['name'];
        }
        $orderState->send_email = $orderStatusData['send_mail'];
        $orderState->color = $orderStatusData['color'];
        $orderState->unremovable = false;
        $orderState->hidden = false;
        $orderState->delivery = false;
        $orderState->logable = $orderStatusData['logable'];
        $orderState->invoice = $orderStatusData['invoice'];
        $orderState->template = $orderStatusData['template'];
        $orderState->paid = $orderStatusData['paid'];
        $orderState->module_name = 'multisafepayofficial';

        if (!$orderState->add()) {
            LoggerHelper::log(
                'error',
                sprintf(
                    'Upgrade 6.2.0 failed: could not create order state for config key %s.',
                    $statusConfigKey
                )
            );

            return false;
        }

        if (!Configuration::updateGlobalValue($statusConfigKey, (int)$orderState->id)) {
            LoggerHelper::log(
                'error',
                sprintf(
                    'Upgrade 6.2.0 failed: could not persist order state id for config key %s.',
                    $statusConfigKey
                )
            );

            return false;
        }

        if (!Configuration::updateGlobalValue($statusReadyFlagKey, '1')) {
            LoggerHelper::log(
                'error',
                sprintf(
                    'Upgrade 6.2.0 failed: could not set ready flag %s after creating order state.',
                    $statusReadyFlagKey
                )
            );

            return false;
        }

        return true;
    }

    $updated = Configuration::updateGlobalValue($statusReadyFlagKey, '1');

    if (!$updated) {
        LoggerHelper::log(
            'error',
            sprintf(
                'Upgrade 6.2.0 failed: could not set ready flag %s for existing order state key %s.',
                $statusReadyFlagKey,
                $statusConfigKey
            )
        );
    }

    return $updated;
}
