<?php

/**
 * Implementation of the order resource.
 *
 * @category  Payment
 */

class Coin_Api implements Coin_Api_ResourceInterface
{


    const API_URL = 'https://alpha-api.coinpayments.net';
    const API_VERSION = '1';

    const API_SIMPLE_INVOICE_ACTION = 'invoices';
    const API_WEBHOOK_ACTION = 'merchant/clients/%s/webhooks';
    const API_MERCHANT_INVOICE_ACTION = 'merchant/invoices';
    const API_CURRENCIES_ACTION = 'currencies';
    const API_CHECKOUT_ACTION = 'checkout';
    const FIAT_TYPE = 'fiat';

    /**
     * Connector.
     *
     * @var Coin_Api_ConnectorInterface
     */
    protected $connector;

    /**
     * string @var
     */
    protected $client_id;

    /**
     * bool @var
     */
    protected $webhooks;

    /**
     * string @var
     */
    protected $client_secret;

    /**
     * @var Context
     */
    protected $context;

    /**
     * Create a new Order object.
     *
     * @param Coin_Api_ConnectorInterface $connector connector to use
     * @param $client_id
     * @param $webhooks
     * @param $client_secret
     */
    public function __construct(
        Coin_Api_ConnectorInterface $connector,
        $client_id,
        $webhooks,
        $client_secret
    )
    {
        $this->connector = $connector;
        $this->client_id = $client_id;
        $this->webhooks = $webhooks;
        $this->client_secret = $client_secret;

        $this->context = Context::getContext();
    }

    /**
     * @return bool|mixed
     */
    public function validate()
    {
        $valid = false;
        try {
            if ($this->useWebhooks()) {
                $valid = $this->validateWebhooks();
            } else {
                $valid = $this->validateInvoice();
            }
        } catch (Exception $e) {
        }

        return $valid;
    }

    /**
     * @return bool
     */
    public function useWebhooks()
    {
        return $this->webhooks;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function validateWebhooks()
    {
        $exists = false;
        $webhooks_list = $this->getWebhooksList();
        if (!empty($webhooks_list)) {
            $webhooks_urls_list = array();
            if (!empty($webhooks_list['items'])) {
                $webhooks_urls_list = array_map(function ($webHook) {
                    return $webHook['notificationsUrl'];
                }, $webhooks_list['items']);
            }
            if (in_array($this->getNotificationUrl(), $webhooks_urls_list)) {
                $exists = true;
            } else {
                if (!empty($webhooks = $this->createWebhook())) {
                    $exists = true;
                }
            }
        }

        return $exists;
    }

    /**
     * @param $client_id
     * @return bool
     * @throws Exception
     */
    public function validateInvoice()
    {
        $invoice = $this->createSimpleInvoice();
        return !empty($invoice['id']);
    }

    /**
     * @param $invoice_id
     * @param $currency_id
     * @param $amount
     * @param $display_value
     * @return mixed
     * @throws Exception
     */
    public function createInvoice($invoice_id, $currency_id, $amount, $display_value)
    {

        $invoice = false;
        if ($this->useWebhooks()) {
            $invoice = $this->createMerchantInvoice($invoice_id, $currency_id, $amount, $display_value);
        } else {
            $invoice = $this->createSimpleInvoice($invoice_id, $currency_id, $amount, $display_value);
        }

        return json_decode($invoice->getData(), true);
    }

    /**
     * @param int $currency_id
     * @param string $invoice_id
     * @param int $amount
     * @param string $display_value
     * @return bool|mixed
     * @throws Exception
     */
    public function createSimpleInvoice($invoice_id = 'Validate invoice', $currency_id = 5057, $amount = 1, $display_value = '0.01')
    {

        $action = self::API_SIMPLE_INVOICE_ACTION;

        $params = array(
            'clientId' => $this->client_id,
            'invoiceId' => $invoice_id,
            'amount' => [
                'currencyId' => $currency_id,
                "displayValue" => $display_value,
                'value' => $amount
            ],
        );

        $params = $this->appendInvoiceMetadata($params);
        return $this->connector->apply('POST', $action, $params);
    }

    /**
     * @param $currency_id
     * @param $invoice_id
     * @param $amount
     * @param $display_value
     * @return bool|mixed
     * @throws Exception
     */
    public function createMerchantInvoice($invoice_id, $currency_id, $amount, $display_value)
    {

        $action = self::API_MERCHANT_INVOICE_ACTION;

        $params = array(
            "invoiceId" => $invoice_id,
            "amount" => [
                "currencyId" => $currency_id,
                "displayValue" => $display_value,
                "value" => $amount
            ],
        );

        $params = $this->appendInvoiceMetadata($params);
        return $this->connector->apply('POST', $action, $params, true);
    }

    /**
     * @return bool|mixed
     * @throws Exception
     */
    public function getWebhooksList()
    {
        $action = sprintf(self::API_WEBHOOK_ACTION, $this->client_id);
        return $this->connector->apply('GET', $action, null, true);
    }

    /**
     * @return bool|mixed
     * @throws Exception
     */
    public function createWebHook()
    {

        $action = sprintf(self::API_WEBHOOK_ACTION, $this->client_id);

        $params = array(
            "notificationsUrl" => $this->getNotificationUrl(),
            "notifications" => [
                "invoiceCreated",
                "invoicePending",
                "invoicePaid",
                "invoiceCompleted",
                "invoiceCancelled",
            ],
        );

        return $this->connector->apply('POST', $action, $params, true);
    }

    /**
     * @param $name
     * @return mixed
     * @throws Exception
     */
    public function getCoinCurrency($name)
    {

        $params = array(
            'types' => self::FIAT_TYPE,
            'q' => $name,
        );
        $items = array();

        $listData = $this->getCoinCurrencies($params);
        if (!empty($listData['items'])) {
            $items = $listData['items'];
        }

        return array_shift($items);
    }

    /**
     * @param array $params
     * @return bool|mixed
     * @throws Exception
     */
    public function getCoinCurrencies($params = array())
    {
        return $this->connector->apply('GET', self::API_CURRENCIES_ACTION, $params);
    }

    /**
     * @param $signature
     * @param $content
     * @return bool
     */
    public function checkDataSignature($signature, $content)
    {
        $request_url = $this->getNotificationUrl();
        $signature_string = sprintf('%s%s', $request_url, $content);
        $digester = new Coin_Api_Digest($this->client_id, $this->client_secret);
        $encoded_pure = $digester->create($signature_string);
        return $signature == $encoded_pure;
    }

    /**
     * @param $request_data
     * @return mixed
     */
    protected function appendInvoiceMetadata($request_data)
    {
        $request_data['metadata'] = array(
            "integration" => sprintf("Presta shop v%s", _PS_VERSION_),
            "hostname" => $this->context->shop->getBaseURL(true, true),
        );

        return $request_data;
    }

    /**
     * @return string
     */
    protected function getNotificationUrl()
    {
        return $this->context->link->getModuleLink('coinpayments', 'notification');
    }

    /**
     * @param $cart_id
     * @return string
     */
    public function getTransactionInvoiceId($cart_id)
    {
        return sprintf('%s|%s', md5($this->context->shop->getBaseURL(true, true)), $cart_id);
    }
}
