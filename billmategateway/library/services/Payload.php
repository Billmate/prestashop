<?php

require_once(_PS_MODULE_DIR_ . 'billmategateway/library/clients/Client.php');

class Payload
{
    private $client;

    public function __construct()
    {
        $this->client = new Client;
    }

    public function verify()
    {
        if (!$this->client->verifyPayload()) {
            $this->logEvent('Payload data from Billmate not valid');

            return false;
        }

        if (!$this->client->verifyPaymentData()) {
            $this->logEvent('Payment data from Billmate not valid');

            return false;
        }

        if (!$this->client->getCartId()) {
            $this->logEvent('Cart id from Billmate is missing');

            return false;
        }

        if (!$this->client->getOrderId()) {
            $this->logEvent('Order id from Billmate is missing');

            return false;
        }

        return true;
    }

    public function getClient()
    {
        return $this->client;
    }

    private function logEvent(...$args)
    {
        // @todo: try/catch...
        PrestaShopLogger::addLog(
            sprintf($args)
        );
    }
}
