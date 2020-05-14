<?php


/**
 * Factory of HTTP Transport.
 *
 * @category   Payment
 */
class Coin_Api_HTTP_Transport
{
    /**
     * Create a new transport instance.
     *
     * @return Coin_Api_HTTP_Transport
     */
    public static function create()
    {
        return new Coin_Api_HTTP_CURLTransport(
            new Coin_Api_HTTP_CURLFactory()
        );
    }
}
