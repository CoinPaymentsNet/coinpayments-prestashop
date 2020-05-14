<?php

class Coin_Api_Digest
{

    protected $client_id;
    protected $client_secret;

    public function __construct($client_id, $client_secret)
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
    }

    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @param $method
     * @param $api_url
     * @param $date
     * @param $params
     * @return string
     */
    public function encode(
        $method,
        $api_url,
        $date,
        $params
    )
    {

        if (!empty($params)) {
            $params = json_encode($params);
        }

        $signature_data = array(
            chr(239),
            chr(187),
            chr(191),
            $method,
            $api_url,
            $this->client_id,
            $date->format('c'),
            $params
        );

        return implode('', $signature_data);
    }

    /**
     * create a digest from a supplied string.
     *
     * @param $signature_string
     * @return string Base64 and SHA256 hashed string
     */
    public function create($signature_string)
    {
        return base64_encode(hash_hmac('sha256', $signature_string, $this->client_secret, true));
    }
}
