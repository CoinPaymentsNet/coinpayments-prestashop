<?php

/**
 * Interface for the resource object.
 *
 * @category  Payment
 */
interface Coin_Api_ConnectorInterface
{

    /**
     * Return content type of the resource.
     *
     * @return string Content type
     */
    public function getContentType();

    /**
     * Applying the method on the specific resource.
     *
     * @param string $method Http methods
     * @param string $action
     * @param array $options Options
     * @param bool $authorized
     */
    public function apply(
        string $method,
        string $action,
        array $options = null,
        bool $authorized = false
    );

}
