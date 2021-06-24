<?php
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

if (!defined('_PS_VERSION_')) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use MultiSafepay\PrestaShop\PaymentOptions\Gateways;
use MultiSafepay\PrestaShop\Services\OrderStatusService;

class Multisafepay extends PaymentModule
{

    const MULTISAFEPAY_MODULE_VERSION = '5.0.0';

    protected $config_form = false;

    /**
     * Multisafepay plugin constructor.
     * @todo Check if we need an instance on load admin. Until now, we don`t
     */
    public function __construct()
    {
        $this->name          = 'multisafepay';
        $this->tab           = 'payments_gateways';
        $this->version       = self::MULTISAFEPAY_MODULE_VERSION;
        $this->author        = 'MultiSafepay';
        $this->need_instance = 1;
        $this->bootstrap     = true;
        parent::__construct();

        $this->displayName            = $this->l('MultiSafepay');
        $this->description            = $this->l('MultiSafepay payment plugin for PrestaShop');
        $this->confirmUninstall       = $this->l('Are you sure you want to uninstall MultiSafepay?');
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);
    }

    /**
     * @return string
     */
    public static function getVersion(): string
    {
        return self::MULTISAFEPAY_MODULE_VERSION;
    }

    /**
     * Install method
     * @see http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     * @todo Don't forget to create update methods if needed:
     */
    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }
        Configuration::updateValue('MULTISAFEPAY_TEST_MODE', false);
        include(__DIR__.'/sql/install.php');

        (new OrderStatusService())->registerMultiSafepayOrderStatuses();

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('actionEmailSendBefore');
    }

    /**
     * Uninstall method
     * @return bool
     */
    public function uninstall()
    {
        Configuration::deleteByName('MULTISAFEPAY_TEST_MODE');
        Configuration::deleteByName('MULTISAFEPAY_API_KEY');
        Configuration::deleteByName('MULTISAFEPAY_TEST_API_KEY');
        include(__DIR__ .'/sql/uninstall.php');
        return parent::uninstall();
    }

    /**
     * Load the configuration form or process the submitted data
     */
    public function getContent()
    {
        if (((bool)Tools::isSubmit('submitMultisafepayModule')) == true) {
            $this->postProcess();
        }
        return $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMultisafepayModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Test mode'),
                        'name' => 'MULTISAFEPAY_TEST_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in test mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter a valid live API key'),
                        'name' => 'MULTISAFEPAY_API_KEY',
                        'label' => $this->l('Live API key'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter a valid test API key'),
                        'name' => 'MULTISAFEPAY_TEST_API_KEY',
                        'label' => $this->l('Test API key'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'MULTISAFEPAY_TEST_MODE' => Configuration::get('MULTISAFEPAY_TEST_MODE'),
            'MULTISAFEPAY_API_KEY' => Configuration::get('MULTISAFEPAY_API_KEY'),
            'MULTISAFEPAY_TEST_API_KEY' => Configuration::get('MULTISAFEPAY_TEST_API_KEY'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     */
    public function hookPayment($params)
    {
        $currency_id = $params['cart']->id_currency;
        $currency = new Currency((int)$currency_id);

        if (in_array($currency->iso_code, $this->limited_currencies) == false) {
            return false;
        }

        $this->smarty->assign('module_dir', $this->_path);

        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {
        if ($this->active == false) {
            return;
        }

        $order = $params['order'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }

        return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
    }

    /**
     * Return payment options available for PS 1.7+
     *
     * @param array Hook parameters
     *
     * @return array|null
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $payment_options = array();
        $payment_methods = Gateways::getMultiSafepayPaymentOptions($this);

        foreach ($payment_methods as $payment_method) {
            $option = new PaymentOption();
            $option->setCallToActionText($payment_method->call_to_action_text);
            $option->setAction($payment_method->action);


            if ($payment_method->icon) {
//                $resize = ImageManager::resize(_PS_MODULE_DIR_ . $this->name . '/views/img/' . $payment_method->icon, _PS_MODULE_DIR_ . $this->name . '/views/img/resize', 105, 45, 'png');
                if ($resize) {
                    $option->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/resize' . $payment_method->icon));
                } else {
                    $option->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/' . $payment_method->icon));
                }
            }

            if ($payment_method->payment_form) {
                $option->setForm($this->getMultiSafepayPaymentOptionForm($payment_method->gateway_code, $payment_method->inputs));
            }

            if ($payment_method->description) {
                $option->setAdditionalInformation($payment_method->description);
            }

            $payment_options[] = $option;
        }

        return $payment_options;
    }

    /**
     * Return payment form
     *
     * @return false|string
     * @throws SmartyException
     */
    public function getMultiSafepayPaymentOptionForm(string $gateway_code, array $inputs = array())
    {
        $this->context->smarty->assign(
            array(
                'action'       => $this->context->link->getModuleLink($this->name, 'payment', array(), true),
                'inputs'       => $inputs
            )
        );
        return $this->context->smarty->fetch('module:multisafepay/views/templates/front/form.tpl');
    }

    /**
     * Disable send emails on order confirmation
     *
     * @param  array $params
     * @return bool
     */
    public function hookActionEmailSendBefore(array $params): bool
    {
        if ($this->isMultiSafepayTemplateVarsSetAsNoSendEmail($params)) {
            return false;
        }
        return true;
    }

    /**
     * @param  array $params
     * @return bool
     */
    private function isMultiSafepayTemplateVarsSetAsNoSendEmail(array $params): bool
    {
        if (isset($params['templateVars']['dont_send_email']) &&  $params['templateVars']['dont_send_email'] === true) {
            return true;
        }
        return false;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

}
