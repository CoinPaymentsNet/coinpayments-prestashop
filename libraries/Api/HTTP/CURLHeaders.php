<?php

/**
 * A simple class handling the header callback for cURL.
 *
 * @category   Payment
 */
class Coin_Api_HTTP_CURLHeaders
{
    /**
     * Response headers, cleared for each request.
     *
     * @var array
     */
    protected $headers;

    /**
     * Initializes a new instance of the HTTP cURL class.
     */
    public function __construct()
    {
        $this->headers = array();
    }

    /**
     * Callback method to handle custom headers.
     *
     * @param resource $curl   the cURL resource.
     * @param string   $header the header data.
     *
     * @return int the number of bytes handled.
     */
    public function processHeader($curl, $header)
    {
        $curl = null;
        //TODO replace with regexp, e.g. /^([^:]+):([^:]*)$/ ?
        $pos = strpos($header, ':');
        // Didn't find a colon.
        if ($pos === false) {
            // Not real header, abort.
            return strlen($header);
        }

        $key = substr($header, 0, $pos);
        $value = trim(substr($header, $pos + 1));

        $this->headers[$key] = trim($value);

        return strlen($header);
    }

    /**
     * Gets the accumulated headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
