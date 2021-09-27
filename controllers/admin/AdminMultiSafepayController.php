<?php

class AdminMultiSafepayController extends ModuleAdminController
{
    /**
     * @return bool|ObjectModel|void
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        Tools::redirectAdmin(
        /* @phpstan-ignore-next-line */
            $this->context->link->getAdminLink(
                'AdminModules',
                true,
                [],
                [
                    'configure' => 'multisafepay',
                ]
            )
        );
    }
}
