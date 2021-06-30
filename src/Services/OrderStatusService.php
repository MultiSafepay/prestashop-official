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

namespace MultiSafepay\PrestaShop\Services;

use OrderState;
use Configuration;
use Tools;
use Language;

/**
 * Class OrderStatusHelper
 *
 */
class OrderStatusService
{

    /**
     *
     */
    public function registerMultiSafepayOrderStatuses(): void
    {
        $multisafepay_order_statuses = $this->getMultiSafepayOrderStatuses();
        foreach ($multisafepay_order_statuses as $multisafepay_order_status_key => $multisafepay_order_status_values) {
            if (!Configuration::get('MULTISAFEPAY_OS_' . Tools::strtoupper($multisafepay_order_status_key))) {
                $order_state = $this->createOrderStatus($multisafepay_order_status_key, $multisafepay_order_status_values);
                Configuration::updateValue('MULTISAFEPAY_OS_' . Tools::strtoupper($multisafepay_order_status_key), (int) $order_state->id);
            }
        }
    }

    /**
     * @param string $multisafepay_order_status_key
     * @param array $multisafepay_order_status_values
     * @return OrderState
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function createOrderStatus(string $multisafepay_order_status_key, array $multisafepay_order_status_values): OrderState
    {
        $order_state              = new OrderState();
        $order_state->name        = 'MultiSafepay ' . $multisafepay_order_status_values['name'];
        $order_state->send_email  = $multisafepay_order_status_values['send_mail'];
        $order_state->color       = $multisafepay_order_status_values['color'];
        $order_state->unremovable = true;
        $order_state->hidden      = false;
        $order_state->delivery    = false;
        $order_state->logable     = $multisafepay_order_status_values['logable'];
        $order_state->invoice     = $multisafepay_order_status_values['invoice'];
        $order_state->template    = $multisafepay_order_status_values['template'];
        $order_state->paid        = $multisafepay_order_status_values['paid'];
        $order_state->add();
        return $order_state;
    }

    /**
     * Return an array with MultiSafepay order statuses
     *
     * @return array
     */
    private function getMultiSafepayOrderStatuses(): array
    {
        return array(
            'initialized' => array(
                'name'      => 'initialized',
                'send_mail' => false,
                'color'     => '#4169E1',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ),
            'uncleared' => array(
                'name'      => 'uncleared',
                'send_mail' => false,
                'color'     => '#ec2e15',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ),
            'partial_refunded' => array(
                'name'      => 'partial refunded',
                'send_mail' => true,
                'color'     => '#ec2e15',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ),
            'chargeback' => array(
                'name'      => 'chargeback',
                'send_mail' => true,
                'color'     => '#ec2e15',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ),
            'awaiting_bank_transfer_payment' => array(
                'name' => 'awaiting bank transfer payment',
                'send_mail' => false,
                'color' => '#4169E1',
                'invoice' => false,
                'template' => '',
                'paid' => false,
                'logable' => false
            ),
        );
    }
}
