<?php

/**
 * Coinpayments.NET API HTTP Response class.
 *
 * @category   Payment
 */
class Coin_Api_HTTP_Response
{
    /**
     * @var int
     */
    protected $status;

    /**
     * @var Coin_Api_HTTP_Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var string
     */
    protected $data;

    /**
     * Initializes a new instance of the HTTP response class.
     *
     * @param Coin_Api_HTTP_Request $request the origin request.
     * @param array               $headers the response HTTP headers.
     * @param int                 $status  the HTTP status code.
     * @param string              $data    the response payload.
     */
    public function __construct(
        Coin_Api_HTTP_Request $request, array $headers, $status, $data
    ) {
        $this->request = $request;
        $this->headers = array();
        foreach ($headers as $key => $value) {
            $this->headers[strtolower($key)] = $value;
        }
        $this->status = $status;
        $this->data = $data;
    }

    /**
     * Gets the HTTP status code.
     *
     * @return int HTTP status code.
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Gets the HTTP request this response originated from.
     *
     * @return Coin_Api_HTTP_Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Gets specified HTTP header.
     *
     * @param string $name the header name.
     *
     * @throws InvalidArgumentException If the specified argument
     *                                  is not of type string.
     *
     * @return string|null Null if header doesn't exist, else header value.
     */
    public function getHeader($name)
    {
        $name = strtolower($name);
        if (!array_key_exists($name, $this->headers)) {
            return;
        }

        return $this->headers[$name];
    }

    /**
     * Gets the headers specified for the response.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Gets the data (payload) for the response.
     *
     * @return string the response payload.
     */
    public function getData()
    {
        return $this->data;
    }
}
