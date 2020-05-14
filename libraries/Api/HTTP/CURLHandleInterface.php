<?php

interface Coin_Api_HTTP_CURLHandleInterface
{
    /**
     * Set an option for the cURL transfer.
     *
     * @param int $name option the set
     * @param mixed $value the value to be set on option
     */
    public function setOption($name, $value);

    /**
     * Perform the cURL session.
     *
     * @return mixed response
     */
    public function execute();

    /**
     * Get information regarding this transfer.
     *
     * @return array
     */
    public function getInfo();

    /**
     * Close the cURL session.
     */
    public function close();
}
