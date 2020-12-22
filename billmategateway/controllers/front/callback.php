<?php

require_once(_PS_MODULE_DIR_ . 'billmategateway/library/abstracts/CallbackController.php');

class BillmategatewayCallbackModuleFrontController extends CallbackController
{
    public $request = 'callback';

    public function __construct()
    {
        parent::__construct();
    }

    public function postProcess()
    {
        if ($this->method == 'checkout' && !Configuration::get('PS_GUEST_CHECKOUT_ENABLED')) {
            Configuration::updateGlobalValue('PS_GUEST_CHECKOUT_ENABLED', 1);
        }

        // Check if payment data is valid
        if (!$this->client->verifyPaymentData()) {
            return $this->respondWithError();
        }

        // Get cart with id from Billmate
        $cartId = $this->client->getCartId();

        // Get cart or fail
        if (!$cart = $this->cartHelper->getCart($cartId)) {
            return $this->respondWithError();
        }

        // If order exists, update it and show success page
        if ($order = $this->orderHelper->getOrderByCart($cart)) {
            $this->updateOrderStatus($order);

            // Show success page
            return $this->respondWithSuccess();
        }

        // Get customer or fail
        if (!$customer = $this->customerHelper->getOrCreateCustomer($cart)) {
            return $this->respondWithError();
        }

        // Update customer with data from Billmate
        $customer = $this->customerHelper->updateCustomer(
            $customer,
            $this->client->getCustomer()
        );

        // Create billing address with data from Billmate
        $billingAddress = $this->customerHelper->createAddress(
            $customer,
            $this->client->getCustomer(),
            false
        );

        // Create shipping address with data from Billmate
        $shippingAddress = $this->customerHelper->createAddress(
            $customer,
            $this->client->getCustomer(),
            true
        );

        // Update cart with new ids
        $cart->id_customer = $customer->id;
        $cart->id_address_invoice = $billingAddress->id;
        $cart->id_address_delivery = $shippingAddress->id;

        // @todo: recalculate shipping base on shipping address...

        $cart->update();
        $cart->save();

        // Create new order or fail
        if (!$order = $this->createOrderFromCart($cart)) {
             return $this->respondWithError();
        }

        // Send order reference to Billmate
        if (!$this->updatePaymentStatus()) {
            return $this->respondWithError();
        }

        // Show success page
        return $this->respondWithSuccess();
    }

    protected function respondWithError(...$arguments)
    {
        header('HTTP/1.1 500 Internal Server Error');
        exit();
    }

    protected function respondWithSuccess(...$arguments)
    {
        header('HTTP/1.1 200 OK');
        exit();
    }
}
