<?php declare(strict_types=1);

use MultiSafepay\PrestaShop\Services\NotificationService;

class MultisafepayNotificationModuleFrontController extends ModuleFrontController
{

    public function __construct()
    {
        parent::__construct();
        $this->ajax = true;
    }

    /**
     * Process notification
     *
     * @return void
     */
    public function postProcess(): void
    {
        /** @var NotificationService $notificationService */
        $notificationService = $this->module->get('multisafepay.notification_service');

        try {
            $notificationService->processNotification(Tools::file_get_contents('php://input'));
        } catch (PrestaShopException $prestaShopException) {
            header('Content-Type: text/plain');
            echo $prestaShopException->getMessage();
        }

        echo ' OK';
    }
}
