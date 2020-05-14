<?php

class Coin_Api_Exception extends Exception
{
}

/**
 * Connector exception.
 * @category  Payment
 */
class Coin_Api_ConnectorException extends Coin_Api_Exception
{
}

/**
 * Connection exception.
 * @category  Payment
 */
class Coin_Api_ConnectionErrorException extends Coin_Api_Exception
{
}
