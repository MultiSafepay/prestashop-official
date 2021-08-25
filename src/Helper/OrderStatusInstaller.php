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

namespace MultiSafepay\PrestaShop\Helper;

use OrderState;
use Configuration;
use Tools;
use Language;

/**
 * Class OrderStatusInstaller
 *
 */
class OrderStatusInstaller
{

    /**
     * Register the MultiSafepay Order statuses
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function registerMultiSafepayOrderStatuses(): void
    {
        $multisafepayOrderStatuses = $this->getMultiSafepayOrderStatuses();
        foreach ($multisafepayOrderStatuses as $multisafepayOrderStatusKey => $multisafepayOrderStatusValues) {
            if (!Configuration::get('MULTISAFEPAY_OS_' . Tools::strtoupper($multisafepayOrderStatusKey))) {
                $orderState = $this->createOrderStatus($multisafepayOrderStatusValues);
                Configuration::updateGlobalValue('MULTISAFEPAY_OS_' . Tools::strtoupper($multisafepayOrderStatusKey), (int) $orderState->id);
            }
        }
    }

    /**
     * Creates the Order Statuses
     *
     * @param array $multisafepayOrderStatusValues
     * @return OrderState
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function createOrderStatus(array $multisafepayOrderStatusValues): OrderState
    {
        $orderState              = new OrderState();
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
        $orderState->module_name = 'multisafepay';
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
            'initialized' => [
                'name'      => 'initialized',
                'send_mail' => false,
                'color'     => '#4169E1',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
            'uncleared' => [
                'name'      => 'uncleared',
                'send_mail' => false,
                'color'     => '#ec2e15',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
            'partial_refunded' => [
                'name'      => 'partial refunded',
                'send_mail' => true,
                'color'     => '#ec2e15',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
            'chargeback' => [
                'name'      => 'chargeback',
                'send_mail' => true,
                'color'     => '#ec2e15',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
            'awaiting_bank_transfer_payment' => [
                'name' => 'awaiting bank transfer payment',
                'send_mail' => false,
                'color' => '#4169E1',
                'invoice' => false,
                'template' => '',
                'paid' => false,
                'logable' => false
            ],
        ];
    }
}
