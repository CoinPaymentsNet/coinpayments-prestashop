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

class CoinpaymentsNotificationModuleFrontController extends ModuleFrontController
{
    /**
     * @var Coinpayments $module
     */
    public $module;

    public $ssl = true;

    public function postProcess()
    {
        try {

            $signature = $_SERVER['HTTP_X_COINPAYMENTS_SIGNATURE'];
            $content = file_get_contents('php://input');
            $request_data = json_decode($content, true);

            $api = $this->module->initCoinApi();

            if (!$api->checkDataSignature($signature, $content)) {
                $error_message = 'CoinPayments Order #' . Tools::getValue('invoice') . ' does not exists';
                throw new Exception($error_message);
            }

            $invoice_str = $request_data['invoice']['invoiceId'];
            $invoice_str = explode('|', $invoice_str);
            $host_hash = array_shift($invoice_str);
            $invoice_id = array_shift($invoice_str);

            if ($host_hash != md5($this->context->shop->getBaseURL(true, true))) {
                $error_message = 'Wrong invoice host';
                throw new Exception($error_message);
            }


            $order_id = Order::getOrderByCartId($invoice_id);
            $order = new Order($order_id);

            if (!$order) {
                $error_message = 'CoinPayments Order #' . Tools::getValue('invoice') . ' does not exists';
                throw new Exception($error_message);
            }

            $status = $request_data['invoice']['status'];

            if ($status == 'Completed') {
                $order_status = 'PS_OS_PAYMENT';
            } else if ($status == 'Cancelled') {
                $order_status = 'coinpayments_expired';
            } else {
                $order_status = 'coinpayments_pending';
            }

            if ($order->getCurrentState() == (int)Configuration::get('coinpayments_pending') || $order->getCurrentState() == (int)Configuration::get('coinpayments_expired')) {
                $history = new OrderHistory();
                $history->id_order = $order->id;
                $history->changeIdOrderState((int)Configuration::get($order_status), $order->id);
                $history->addWithemail(true, array(
                    'order_name' => $order->getUniqReference(),
                ));
            }

            $this->context->smarty->assign(array(
                'text' => 'IPN OK'
            ));
        } catch (Exception $e) {
            $this->context->smarty->assign(array(
                'text' => get_class($e) . ': IPN Error: ' . $e->getMessage()
            ));
        }
        $this->setTemplate('module:coinpayments/views/templates/front/notification.tpl');
    }

}
