<?php

/**
 * A wrapper around the cURL functions.
 *
 * @category   Payment
 */
class Coin_Api_HTTP_CURLHandle
    implements Coin_Api_HTTP_CURLHandleInterface
{
    /**
     * cURL handle.
     *
     * @var resource
     */
    private $_handle = null;

    /**
     * Create a new cURL handle.
     */
    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException(
                'cURL extension is requred.'
            );
        }
        $this->_handle = curl_init();
    }

    /**
     * Set an option for the cURL transfer.
     *
     * @param int   $name  option the set
     * @param mixed $value the value to be set on option
     */
    public function setOption($name, $value)
    {
        curl_setopt($this->_handle, $name, $value);
    }

    /**
     * Perform the cURL session.
     *
     * @return mixed response
     */
    public function execute()
    {
        return curl_exec($this->_handle);
    }

    /**
     * Get information regarding this transfer.
     *
     * @return array
     */
    public function getInfo()
    {
        return curl_getinfo($this->_handle);
    }

    /**
     * Close the cURL session.
     */
    public function close()
    {
        curl_close($this->_handle);
    }
}
