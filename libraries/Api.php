<?php

define('COIN_API_DIR', dirname(__file__) . '/Api');

require_once COIN_API_DIR . '/ConnectorInterface.php';
require_once COIN_API_DIR . '/ResourceInterface.php';
require_once COIN_API_DIR . '/Connector.php';
require_once COIN_API_DIR . '/BasicConnector.php';
require_once COIN_API_DIR . '/Coin_Api.php';
require_once COIN_API_DIR . '/Digest.php';
require_once COIN_API_DIR . '/Exception.php';
require_once COIN_API_DIR . '/UserAgent.php';

require_once COIN_API_DIR . '/HTTP/TransportInterface.php';
require_once COIN_API_DIR . '/HTTP/CURLHandleInterface.php';
require_once COIN_API_DIR . '/HTTP/Request.php';
require_once COIN_API_DIR . '/HTTP/Response.php';
require_once COIN_API_DIR . '/HTTP/Transport.php';
require_once COIN_API_DIR . '/HTTP/CURLTransport.php';
require_once COIN_API_DIR . '/HTTP/CURLHeaders.php';
require_once COIN_API_DIR . '/HTTP/CURLHandle.php';
require_once COIN_API_DIR . '/HTTP/CURLFactory.php';
