<?php


/**
 * Connector factory.
 *
 * @category  Payment
 */
class Coin_Api_Connector
{
    /**
     * Create a new API Connector.
     *
     * @param string $client_id
     *
     * @param string $webhooks
     *
     * @param string $client_secret
     *
     * @return Coin_Api_ConnectorInterface
     */
    public static function create($client_id, $client_secret)
    {
        return new Coin_Api_BasicConnector(
            Coin_Api_HTTP_Transport::create(),
            new Coin_Api_Digest($client_id, $client_secret)
        );
    }
}
