<?php declare(strict_types=1);

use MultiSafepay\PrestaShop\Services\TokenizationService;

class MultisafepayTokensModuleFrontController extends ModuleFrontController
{

    /**
     * @return void
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function postProcess()
    {
        parent::postProcess();

        if (Tools::isSubmit('submitRemoveToken')) {

            /** @var TokenizationService $tokenizationService */
            $tokenizationService = $this->get('multisafepay.tokenization_service');

            if ($tokenizationService->deleteToken((string)$this->context->customer->id, Tools::getValue('tokenId'))) {
                $this->success[] = $this->module->l('Payment details have been removed');
            } else {
                $this->errors[] = $this->module->l('There was an error while deleting the payment details');
            }
        }
    }

    /**
     * @return string
     * @throws PrestaShopException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function initContent()
    {
        parent::initContent();

        /** @var TokenizationService $tokenizationService */
        $tokenizationService = $this->get('multisafepay.tokenization_service');

        $this->context->smarty->assign([
            'action' => $this->getCurrentURL(),
            'tokens' => $tokenizationService->getTokensForCustomerAccount(),
        ]);

        return $this->setTemplate('module:multisafepay/views/templates/front/tokens.tpl');
    }

    /**
     * @return array
     */
    public function getTemplateVarPage()
    {
        $tplVars = parent::getTemplateVarPage();
        $tplVars['body_classes']['page-customer-account'] = true;
        return $tplVars;
    }

    /**
     * @return array
     */
    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
        $breadcrumb['links'][] = [
            'title' => $this->trans('Saved payment details', [], 'multisafepay'),
            'url' => $this->context->link->getModuleLink('multisafepay', 'tokens'),
        ];

        return $breadcrumb;
    }
}
