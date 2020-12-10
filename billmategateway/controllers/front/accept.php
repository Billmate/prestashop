<?php

require_once(_PS_MODULE_DIR_ . 'billmategateway/library/abstracts/CallbackController.php');

class BillmategatewayAcceptModuleFrontController extends CallbackController
{
    public $request = 'accept';

    public function __construct()
    {
        parent::__construct();
    }

    public function postProcess()
    {
         // Get cart with id from Billmate
        $cartId = $this->client->getCartId();

        // Get cart or fail
        if (!$cart = $this->cartHelper->getCart($cartId)) {
            return $this->respondWithError();
        }

        // Show success page
        return $this->respondWithSuccess();
    }

    protected function respondWithError()
    {
        die($this->context->link->getModuleLink('billmategateway', 'failed', ['id' => 123]));
        Tools::redirect(
            $this->context->link->getModuleLink('billmategateway', 'failed', ['id' => 123])
        );
    }

    protected function respondWithSuccess()
    {
        die($this->context->link->getModuleLink('billmategateway', 'failed', ['id' => 123]));
        Tools::redirect('index.php?controller=billmate-order-confirmation');
    }
}
