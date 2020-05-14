<?php

/**
 * Factory of cURL handles.
 *
 * @category   Payment
 */
class Coin_Api_HTTP_CURLFactory
{
    /**
     * Create a new cURL handle.
     *
     * @return Coin_Api_HTTP_CURLHandle
     */
    public function handle()
    {
        return new Coin_Api_HTTP_CURLHandle();
    }
}
