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
class CoinpaymentsRedirectModuleFrontController extends ModuleFrontController
{
    /**
     * @var Coinpayments $module
     */
    public $module;

    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;


        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $total = (float)number_format($cart->getOrderTotal(true, 3), 2, '.', '');
        $currency = Context::getContext()->currency;

        $customer = new Customer($cart->id_customer);

        $api = $this->module->initCoinApi();


        $payment_url = sprintf('%s/%s/', Coin_Api::CHECKOUT_URL, Coin_Api::API_CHECKOUT_ACTION);
        $cancel_url = $this->context->link->getModuleLink('coinpayments', 'cancel');

        $link = new Link();
        $success_url = $link->getPageLink('order-confirmation', null, null, array(
            'id_cart' => $cart->id,
            'id_module' => $this->module->id,
            'key' => $customer->secure_key
        ));


        $invoice_id = $api->getTransactionInvoiceId($cart->id);
        try {
            $coin_currency = $api->getCoinCurrency($currency->iso_code);
            $amount = intval(number_format($total, $coin_currency['decimalPlaces'], '', ''));

            foreach ($cart->getAddressCollection() as $address) {
                $address1 = $address->address1;
                $postcode = $address->postcode;
                $city = $address->city;
                $state = (new State($address->id_state))->name;
                $country = (new Country($address->id_country))->name[$this->context->language->id];
            }
            $billing_data = array(
                'companyname' => $customer->company,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
                'email' => $customer->email,
                'address1' => $address1,
                'city' => $city,
                'country' => $country,
                'postcode' => $postcode,
                'state' => $state,
            );
            $position = strripos(Configuration::get('admin_link'), "orders") + 6;
            $admin_link = Configuration::get('admin_link');
            $admin_link = substr($admin_link, 0, $position) . "/" . $cart->id . '/view' . substr($admin_link, $position + 1);
            $invoice_params = array(
                'invoice_id' => $invoice_id,
                'currency_id' => $coin_currency['id'],
                'amount' => $amount,
                'display_value' => $total,
                'billing_data' => $billing_data,
                'notes_link' => sprintf("%s|Store name: %s|Order #%s", substr($this->context->shop->getBaseURL(true, true), 0, -1) . $admin_link, Configuration::get('PS_SHOP_NAME'), $cart->id)
            );

            $invoice = $api->createInvoice($invoice_params);

        } catch (Exception $e) {
            $error = $e;
        }

        $customer = new Customer($cart->id_customer);
        $this->module->validateOrder(
            $cart->id,
            Configuration::get('coinpayments_pending'),
            $total,
            $this->module->displayName,
            null,
            null,
            (int)$currency->id,
            false,
            $customer->secure_key
        );

        $this->context->smarty->assign(array(
            'payment_url' => $payment_url,
            'params' => array(
                'invoice-id' => $invoice['id'],
                'success-url' => $success_url,
                'cancel-url' => $cancel_url,

            )
        ));

        $this->setTemplate('module:coinpayments/views/templates/front/payment_form.tpl');
    }
}
