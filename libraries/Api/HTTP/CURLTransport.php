<?php

/**
 * Coinpayments.NET API HTTP transport implementation for cURL.
 *
 * @category   Payment
 */
class Coin_Api_HTTP_CURLTransport
    implements Coin_Api_HTTP_TransportInterface
{
    protected $curl;

    /**
     * Number of seconds before the connection times out.
     *
     * @var int
     */
    protected $timeout;

    /**
     * Initializes a new instance of the HTTP cURL class.
     *
     * @param Coin_Api_HTTP_CURLFactory $curl factory to for curl handles
     */
    public function __construct(Coin_Api_HTTP_CURLFactory $curl)
    {
        $this->curl = $curl;
        $this->timeout = 5; // default to 5 seconds
    }

    /**
     * Sets the number of seconds until a connection times out.
     *
     * @param int $timeout number of seconds
     */
    public function setTimeout($timeout)
    {
        $this->timeout = intval($timeout);
    }

    /**
     * Gets the number of seconds before the connection times out.
     *
     * @return int timeout in number of seconds
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Performs a HTTP request.
     *
     * @param Coin_Api_HTTP_Request $request the HTTP request to send.
     *
     * @throws RuntimeException                Thrown if a cURL handle cannot
     *                                         be initialized.
     * @throws Coin_Api_ConnectionErrorException Thrown for unspecified network
     *                                         or hardware issues.
     *
     * @return Coin_Api_HTTP_Response
     */
    public function send(Coin_Api_HTTP_Request $request)
    {
        $curl = $this->curl->handle();
        if ($curl === false) {
            throw new RuntimeException(
                'Failed to initialize a HTTP handle.'
            );
        }

        $url = $request->getURL();
        $curl->setOption(CURLOPT_URL, $url);

        $method = $request->getMethod();
        if ($method === 'POST') {
            $curl->setOption(CURLOPT_POST, true);
            $curl->setOption(CURLOPT_POSTFIELDS, $request->getData());
        }

        // Convert headers to cURL format.
        $requestHeaders = array();
        foreach ($request->getHeaders() as $key => $value) {
            $requestHeaders[] = $key.': '.$value;
        }

        $curl->setOption(CURLOPT_HTTPHEADER, $requestHeaders);

        $curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, $this->timeout);

        $curlHeaders = new Coin_Api_HTTP_CURLHeaders();
        $curl->setOption(
            CURLOPT_HEADERFUNCTION,
            array(&$curlHeaders, 'processHeader')
        );

        // TODO remove me when real cert is in place
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);

        $payload = $curl->execute();
        $info = $curl->getInfo();

        $curl->close();

        /*
         * A failure occured if:
         * payload is false (e.g. HTTP timeout?).
         * info is false, then it has no HTTP status code.
         */
        if ($payload === false || $info === false) {
            throw new Coin_Api_ConnectionErrorException(
                "Connection to '{$url}' failed."
            );
        }

        $headers = $curlHeaders->getHeaders();

        // Convert Content-Type into a normal header
        $headers['Content-Type'] = $info['content_type'];

        $response = new Coin_Api_HTTP_Response(
            $request, $headers, intval($info['http_code']), strval($payload)
        );

        return $response;
    }

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
    public function createRequest($url)
    {
        return new Coin_Api_HTTP_Request($url);
    }
}
