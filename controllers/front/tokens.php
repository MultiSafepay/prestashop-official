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

use MultiSafepay\PrestaShop\Services\TokenizationService;
use MultiSafepay\PrestaShop\Services\SdkService;
use Psr\Http\Client\ClientExceptionInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class MultisafepayOfficialTokensModuleFrontController
 *
 * @property MultisafepayOfficial $module
 */
class MultisafepayOfficialTokensModuleFrontController extends ModuleFrontController
{

    /**
     * @return void
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function postProcess()
    {
        parent::postProcess();

        if (Tools::isSubmit('submitRemoveToken')) {
            $tokenizationService = new TokenizationService($this->module, new SdkService());

            if ($tokenizationService->deleteToken((string)$this->context->customer->id, Tools::getValue('tokenId'))) {
                $this->success[] = $this->module->l('Payment details have been removed', 'tokens');
            } else {
                $this->errors[] = $this->module->l('There was an error while deleting the payment details', 'tokens');
            }
        }
    }

    /**
     * @return string|null
     * @throws PrestaShopException
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function initContent(): ?string
    {
        parent::initContent();

        $tokenizationService = new TokenizationService($this->module, new SdkService());

        $this->context->smarty->assign([
            'action' => $this->getCurrentURL(),
            'tokens' => $tokenizationService->getTokensForCustomerAccount($this->context->customer->id),
        ]);

        return $this->setTemplate('module:multisafepayofficial/views/templates/front/tokens.tpl');
    }

    /**
     * @return array
     */
    public function getTemplateVarPage(): array
    {
        $tplVars = parent::getTemplateVarPage();
        $tplVars['body_classes']['page-customer-account'] = true;
        return $tplVars;
    }

    /**
     * @return array
     */
    public function getBreadcrumbLinks(): array
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
        $breadcrumb['links'][] = [
            'title' => $this->trans('Saved payment details', [], 'multisafepayofficial'),
            'url' => $this->context->link->getModuleLink('multisafepayofficial', 'tokens'),
        ];

        return $breadcrumb;
    }
}
