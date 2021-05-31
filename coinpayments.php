<?php
/**
 * NOTICE OF LICENSE
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2020 CoinPayments.net
 * Copyright (c) 2015-2016 CoinGate
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject
 * to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
 * IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author    CoinPayments.net
 * @copyright 2020 CoinPayments, Inc.
 * @author    CoinGate <info@coingate.com>
 * @copyright 2015-2016 CoinGate
 * @license   https://github.com/coingate/prestashop-plugin/blob/master/LICENSE  The MIT License (MIT)
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;


if (!defined('_PS_VERSION_')) {
    exit;
}

class Coinpayments extends PaymentModule
{
    private $html = '';
    private $postErrors = array();

    public $client_id;
    public $webhooks;
    public $client_secret;

    private $admin_link;

    public function __construct()
    {
        $this->name = 'coinpayments';
        $this->tab = 'payments_gateways';
        $this->version = '2.0.0';
        $this->author = 'CoinPayments.net';
        $this->is_eu_compatible = 1;
        $this->controllers = array('redirect', 'callback', 'cancel');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->module_key = '4c644b5f587a4c6a734b4e787631784f';
        $this->bootstrap = true;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';


        $config = Configuration::getMultiple(
            array(
                'coinpayments_client_id',
                'coinpayments_webhooks',
                'coinpayments_client_secret',
                'admin_link'
            )
        );

        if (!empty($config['coinpayments_client_id'])) {
            $this->client_id = $config['coinpayments_client_id'];
        }
        if (!empty($config['coinpayments_webhooks'])) {
            $this->webhooks = $config['coinpayments_webhooks'];
        }
        if (!empty($config['coinpayments_client_secret'])) {
            $this->client_secret = $config['coinpayments_client_secret'];
        }
        if (!empty($config['admin_link'])) {
            $this->admin_link = $config['admin_link'];
        }

        parent::__construct();

        $this->displayName = $this->l('Accept Cryptocurrencies with CoinPayments');
        $this->description = $this->l('Accept Bitcoin and other cryptocurrencies with CoinPayments');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');

        if (!isset($this->client_id) || !isset($this->client_secret)) {
            $this->warning = $this->l('Your Client ID and Client Secret must be configured in order to use this module correctly.');
        }
    }

    public function install()
    {
        $order_pending = new OrderState();
        $order_pending->name = array_fill(0, 10, 'CoinPayments Pending');
        $order_pending->send_email = 0;
        $order_pending->invoice = 0;
        $order_pending->color = 'RoyalBlue';
        $order_pending->unremovable = false;
        $order_pending->logable = 0;

        $order_expired = new OrderState();
        $order_expired->name = array_fill(0, 10, 'CoinPayments Timed Out/Cancelled');
        $order_expired->send_email = 0;
        $order_expired->invoice = 0;
        $order_expired->color = '#800000';
        $order_expired->unremovable = false;
        $order_expired->logable = 0;

        if ($order_pending->add()) {
            copy(
                _PS_ROOT_DIR_ . '/modules/coinpayments/logo.png',
                _PS_ROOT_DIR_ . '/img/os/' . (int)$order_pending->id . '.png'
            );
        }

        if ($order_expired->add()) {
            copy(
                _PS_ROOT_DIR_ . '/modules/coinpayments/logo.png',
                _PS_ROOT_DIR_ . '/img/os/' . (int)$order_expired->id . '.png'
            );
        }

        Configuration::updateValue('coinpayments_pending', $order_pending->id);
        Configuration::updateValue('coinpayments_expired', $order_expired->id);

        if (!parent::install()
            || !$this->registerHook('payment')
            || !$this->registerHook('displayPaymentEU')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('paymentOptions')) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        $order_state_pending = new OrderState(Configuration::get('coinpayments_pending'));
        $order_state_expired = new OrderState(Configuration::get('coinpayments_expired'));

        return (
            Configuration::deleteByName('coinpayments_client_id') &&
            Configuration::deleteByName('coinpayments_webhooks') &&
            Configuration::deleteByName('coinpayments_client_secret') &&
            $order_state_pending->delete() &&
            $order_state_expired->delete() &&
            parent::uninstall()
        );
    }

    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->postValidation();
            if (!count($this->postErrors)) {
                $this->postProcess();
            } else {
                foreach ($this->postErrors as $err) {
                    $this->html .= $this->displayError($err);
                }
            }
        } else {
            $this->html .= '<br />';
        }

        $renderForm = $this->renderForm();
        $this->html .= $this->displayCoinpaymentsInformation($renderForm);

        return $this->html;
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $logo = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/logo.png');
        $logoHtml = "<img src='" . $logo . "' height='100' style='margin: 0px' title='CoinPayments.net' />";

        $coinpayments_link = '<a href="https://alpha.coinpayments.net/" target="_blank" title="CoinPayments.net">CoinPayments.net</a>';
        $coin_description = 'Pay with Bitcoin, Litecoin, or other altcoins via ';
        $description = sprintf('%s<br/>%s<br/>%s<br/>', $logoHtml, $coin_description, $coinpayments_link);

        $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $newOption->setCallToActionText('Bitcoin or other cryptocurrencies with CoinPayments.net')
            ->setAction($this->context->link->getModuleLink($this->name, 'redirect', array(), true))
            ->setAdditionalInformation($description);

        $payment_options = array($newOption);

        return $payment_options;
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

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Accept Cryptocurrencies with CoinPayments.net'),
                    'icon' => 'icon-bitcoin',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Client ID'),
                        'name' => 'coinpayments_client_id',
                        'desc' => $this->l('Your Client ID (found on your Accoint Settings page)'),
                        'required' => true,
                    ),
                    [
                        'type' => 'switch',
                        'onchange' => '',
                        'label' => $this->l('Webhooks'),
                        'name' => 'coinpayments_webhooks',
                        'required' => false,
                        'class' => 't',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                        'desc' => $this->l('Enable to use CoinPayments.NET merchant webhooks.'),
                    ],
                    array(
                        'type' => 'text',
                        'label' => $this->l('Client Secret'),
                        'name' => 'coinpayments_client_secret',
                        'desc' => $this->l('Client Secret (set on your Accoint Settings page, Merchant tab)'),
                        'required' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Update Settings'),
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = (Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0);
        $this->fields_form = array();
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module='
            . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'coinpayments_client_id' => Tools::getValue(
                'coinpayments_client_id',
                Configuration::get('coinpayments_client_id')
            ),
            'coinpayments_webhooks' => Tools::getValue(
                'coinpayments_webhooks',
                Configuration::get('coinpayments_webhooks')
            ),
            'coinpayments_client_secret' => Tools::getValue(
                'coinpayments_client_secret',
                Configuration::get('coinpayments_client_secret')
            ),
            'admin_link' => Tools::getValue(
                'admin_link',
                Configuration::get('admin_link')
            ),
        );
    }

    public function initCoinApi($client_id = null, $webhooks = null, $client_secret = null)
    {

        if (!isset($client_id)) {
            $client_id = $this->client_id;
        }
        if (!isset($webhooks)) {
            $webhooks = $this->webhooks;
        }
        if (!isset($client_secret)) {
            $client_secret = $this->client_secret;
        }

        require_once dirname(__FILE__) . '/libraries/Api.php';

        $connector = Coin_Api_Connector::create($client_id, $client_secret);

        return new Coin_Api($connector, $client_id, boolval($webhooks), $client_secret);
    }

    protected function postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {


            $config_credentials = array(
                'coinpayments_client_id' => Configuration::get('coinpayments_client_id'),
                'coinpayments_webhooks' => Configuration::get('coinpayments_webhooks'),
                'coinpayments_client_secret' => Configuration::get('coinpayments_client_secret'),
            );

            $request_values['coinpayments_client_id'] = Tools::getValue('coinpayments_client_id');
            if (!$request_values['coinpayments_client_id']) {
                $this->postErrors[] = $this->l('Client ID is required.');
            }

            $request_values['coinpayments_webhooks'] = boolval(Tools::getValue('coinpayments_webhooks'));
            $request_values['coinpayments_client_secret'] = Tools::getValue('coinpayments_client_secret');
            if ($request_values['coinpayments_webhooks'] && !$request_values['coinpayments_client_secret']) {
                $this->postErrors[] = $this->l('Client Secret is required.');
            }

            $credentials_updated = implode('', $config_credentials) != implode('', $request_values);

            if (empty($this->postErrors) && $credentials_updated) {

                $api = $this->initCoinApi(
                    Tools::getValue('coinpayments_client_id'),
                    Tools::getValue('coinpayments_webhooks'),
                    Tools::getValue('coinpayments_client_secret')
                );

                if (!$api->validate()) {
                    $this->postErrors[] = $this->l('Your CoinPayments.NET credentials are invalid!');
                }
            }
        }
    }

    protected function postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('coinpayments_client_id', trim(Tools::getValue('coinpayments_client_id')));
            Configuration::updateValue('coinpayments_webhooks', trim(Tools::getValue('coinpayments_webhooks')));
            Configuration::updateValue('coinpayments_client_secret', trim(Tools::getValue('coinpayments_client_secret')));
            Configuration::updateValue('admin_link', $this->context->link->getAdminLink('AdminOrders'));
        }

        $this->html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    protected function displayCoinpayments()
    {
        return $this->display(__FILE__, 'infos.tpl');
    }

    protected function displayCoinpaymentsInformation($renderForm)
    {
        $this->html .= $this->displayCoinpayments();
        $this->context->controller->addCSS($this->_path . '/views/css/tabs.css', 'all');
        $this->context->controller->addJS($this->_path . '/views/js/javascript.js', 'all');
        $this->context->smarty->assign('form', $renderForm);
        return $this->display(__FILE__, 'information.tpl');
    }
}
