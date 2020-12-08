<?php

require_once(_PS_MODULE_DIR_ . 'billmategateway/library/Client.php');
require_once(_PS_MODULE_DIR_ . 'billmategateway/library/ModuleHandler.php');

class BillmategatewayAcceptModuleFrontController extends ModuleFrontController
{
    public $module;

    private $client;
    private $moduleHandler;
    private $method;

    public function __construct()
    {
        $this->method = Tools::getValue('method');
        $this->client = new Client;
        $this->moduleHandler = new ModuleHandler;

        parent::__construct();
    }

    public function postProcess()
    {
        if (!$this->module = $this->moduleHandler->getPaymentModule()) {
            $this->logEvent('Payment module was not valid');

            return $this->respondWithError(1);
        }

        if (!$this->client->verifyPayload()) {
            $this->logEvent('Payload data from Billmate not valid');

            return $this->respondWithError(2);
        }

        if (!$this->client->verifyPaymentData()) {
            $this->logEvent('Payment data from Billmate not valid');

            return $this->respondWithError(3);
        }

        if (!$this->client->getCartId()) {
            $this->logEvent('Cart id from Billmate is missing');

            return $this->respondWithError(4);
        }

        if (!$this->client->getOrderId()) {
            $this->logEvent('Order id from Billmate is missing');

            return $this->respondWithError(5);
        }

        $this->logEvent('Recieved accept request for order #%s', $this->client->getOrderId());

        $cart = new Cart(
            $this->client->getCartId()
        );

        return $this->respondWithSuccess();
    }

    private function logEvent(...$args)
    {
        PrestaShopLogger::addLog(
            sprintf($args)
        );
    }

    private function respondWithError($index = null)
    {
        die('ERROR #' . $index);
        Tools::redirect('index.php?controller=billmate-order-failed');
    }

    private function respondWithSuccess()
    {
        die('SUCCESS');
        Tools::redirect('index.php?controller=billmate-order-confirmation');
    }
}
