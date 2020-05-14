<?php

/**
 * Interface for a Coinpayments.NETHTTP Transport object.
 *
 * @category   Payment
 */
interface Coin_Api_HTTP_TransportInterface
{
    /**
     * Specifies the number of seconds before the connection times out.
     *
     * @param int $timeout number of seconds
     *
     * @throws InvalidArgumentException If the specified argument
     *                                  is not of type integer.
     */
    public function setTimeout($timeout);

    /**
     * Gets the number of seconds before the connection times out.
     *
     * @return int timeout in number of seconds
     */
    public function getTimeout();

    /**
     * Performs a HTTP request.
     *
     * @param Coin_Api_HTTP_Request $request the HTTP request to send.
     *
     * @throws Coin_Api_ConnectionErrorException Thrown for unspecified network
     *                                         or hardware issues.
     *
     * @return Coin_Api_HTTP_Response
     */
    public function send(Coin_Api_HTTP_Request $request);

    /**
     * Creates a HTTP request object.
     *
     * @param string $url the request URL.
     *
     * @throws InvalidArgumentException If the specified argument
     *                                  is not of type string.
     *
     * @return Coin_Api_HTTP_Request
     */
    public function createRequest($url);
}
