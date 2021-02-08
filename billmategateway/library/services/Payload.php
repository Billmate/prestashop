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
            $this->logEvent('Payload from Billmate is not valid');

            return false;
        }

        if (!$this->client->getOrderId()) {
            $this->logEvent('Payload is missing order id');

            return false;
        }

        if (!$this->client->getCartId()) {
            $this->logEvent('Payload is missing cart id');

            return false;
        }

        return true;
    }

    public function getClient()
    {
        return $this->client;
    }

    private function logEvent($message)
    {
        try {
            PrestaShopLogger::addLog(sprintf('[BILLMATE] %s', $message), 1);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
