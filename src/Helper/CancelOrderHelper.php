<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\Helper;

use Configuration;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use OrderHistory;
use PrestaShopCollection;

class CancelOrderHelper
{
    /**
     * Cancel the orders for the given Order Collection
     *
     * @param PrestaShopCollection $orderCollection
     * @return void
     */
    public static function cancelOrder(PrestaShopCollection $orderCollection): void
    {
        foreach ($orderCollection->getResults() as $order) {
            $history  = new OrderHistory();
            $history->id_order = (int)$order->id;
            $history->id_order_state = (int)$order->id;
            $history->changeIdOrderState((int) Configuration::get('PS_OS_CANCELED'), $order->id);
            $history->addWithemail(true, ['dont_send_email' => true]);

            if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
                LoggerHelper::logInfo('Order ID: ' . $order->id . ' has been canceled');
            }
        }
    }
}
