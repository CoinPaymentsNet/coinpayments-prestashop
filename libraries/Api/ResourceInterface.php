<?php

/**
 * Interface for the resource object.
 *
 * @category  Payment
 */
interface Coin_Api_ResourceInterface
{


    /**
     * @return bool
     */
    public function useWebhooks();

    /**
     * @return bool
     */
    public function validateWebhooks();

    /**
     * @return bool
     * @throws Exception
     */
    public function validateInvoice();

    /**
     * @return mixed
     */
    public function validate();
}
