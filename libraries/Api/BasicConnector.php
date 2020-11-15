<?php


class Coin_Api_BasicConnector implements Coin_Api_ConnectorInterface
{

    /**
     * Content Type to use.
     *
     * @var string
     */
    public static $contentType = 'application/json';

    /**
     * Coin_Api_HTTP_HTTPInterface Implementation.
     *
     * @var Coin_Api_HttpInterface
     */
    protected $http;

    /**
     * @var Coin_Api_Digest
     */
    protected $signature_generator;

    /**
     * Create a new API Connector.
     *
     * @param Coin_Api_HTTP_TransportInterface $http transport
     * @param Coin_Api_Digest $digester Digest Generator
     */
    public function __construct(
        Coin_Api_HTTP_TransportInterface $http,
        Coin_Api_Digest $digester
    )
    {
        $this->http = $http;
        $this->signature_generator = $digester;
    }


    /**
     * Return content type of the resource.
     *
     * @return string Content type
     */
    public function getContentType()
    {
        return self::$contentType;
    }

    /**
     * Applying the method on the specific resource.
     *
     * @param string $method Http methods
     * @param string $action
     * @param array $params Options
     *
     * @param bool $authorized
     * @return mixed
     * @throws Coin_Api_ConnectionErrorException
     * @throws Coin_Api_ConnectorException
     */
    public function apply(
        string $method,
        string $action,
        array $params = null,
        bool $authorized = false
    )
    {
        switch ($method) {
            case 'GET':
            case 'POST':
                return $this->
                handle($method, $action, $params, $authorized);
            default:
                throw new InvalidArgumentException(
                    "{$method} is not a valid HTTP method"
                );
        }
    }


    /**
     * Set content (headers, payload) on a request.
     *
     * @param string $method HTTP Method
     * @param $params
     * @param string $url URL for request
     *
     * @param bool $authorized
     * @return Coin_Api_HTTP_Request
     */
    protected function createRequest(
        $method,
        $params,
        $url,
        $authorized = false
    )
    {

        $date = new \Datetime();

        $headers = array(
            'Content-Type' => $this->getContentType(),
        );

        if ($authorized) {
            // Generate the signature string
            $payload = $this->signature_generator->encode($method, $url, $date, $params);
            $signature = $this->signature_generator->create($payload);
            $headers['X-CoinPayments-Client'] = $this->signature_generator->getClientId();
            $headers['X-CoinPayments-Timestamp'] = $date->format('c');
            $headers['X-CoinPayments-Signature'] = $signature;
        }

        if ($method == 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }


        $request = $this->http->createRequest($url);
        $request->setHeaders($headers);
        $request->setMethod($method);
        if ($method == 'POST' && !empty($params)) {
            $request->setData(json_encode($params));
        }

        return $request;
    }

    /**
     * Throw an exception if the server responds with an error code.
     *
     * @param Coin_Api_HTTP_Response $result HTTP Response object
     *
     * @throws Coin_Api_HTTP_Status_Exception
     */
    protected function verifyResponse(Coin_Api_HTTP_Response $result)
    {
        // Error Status Code recieved. Throw an exception.
        if ($result->getStatus() >= 400 && $result->getStatus() <= 599) {
            throw new Coin_Api_ConnectorException(
                $result->getData(), $result->getStatus()
            );
        }
    }

    /**
     * Act upon the status of a response.
     *
     * @param Coin_Api_HTTP_Response $result response from server
     *
     * @return Coin_Api_HTTP_Response
     * @throws Coin_Api_ConnectorException
     */
    protected function handleResponse(
        Coin_Api_HTTP_Response $result
    )
    {
        // Check if we got an Error status code back
        $this->verifyResponse($result);

        $url = $result->getHeader('Location');
        switch ($result->getStatus()) {
            case 401:
                throw new Coin_Api_ConnectorException(
                    'CoinPayments.NET unauthorized request.',
                    401
                );
            case 200:
            case 201:
                // Update Data on resource
                $json = json_decode($result->getData(), true);
                if ($json === null) {
                    throw new Coin_Api_ConnectorException(
                        'Bad format on response content.',
                        -2
                    );
                }
                return $json;
        }

        return $result;
    }

    /**
     * Perform a HTTP Call on the supplied resource using the wanted method.
     *
     * @param string $method HTTP Method
     * @param string $action
     * @param array $params Options
     * @param bool $authorized
     *
     * @return Coin_Api_HTTP_Response object containing status code and payload
     * @throws Coin_Api_ConnectionErrorException
     * @throws Coin_Api_ConnectorException
     */
    protected function handle(
        $method,
        string $action,
        array $params = null,
        bool $authorized = false
    )
    {

        $url = $this->getApiUrl($action);

        // Create a HTTP Request object
        $request = $this->createRequest($method, $params, $url, $authorized);

        // Execute the HTTP Request
        $result = $this->http->send($request);

        // Handle statuses appropriately.
        return $this->handleResponse($result);
    }

    /**
     * Get the url to use.
     * @return string Url to use for HTTP requests
     */
    protected function getApiUrl(string $action)
    {
        return sprintf('%s/api/v%s/%s', Coin_Api::API_URL, Coin_Api::API_VERSION, $action);
    }
}
