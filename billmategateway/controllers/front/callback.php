<?php

class BillmategatewayAcceptModuleFrontController extends CallbackController
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

        // @todo: add shipping...

        $cart->update();
        $cart->save();

        // Create new order or fail
        if (!$order = $this->createOrderFromCart($cart)) {
             return $this->respondWithError();
        }

        // Send order reference to Billmate
        $this->updatePaymentStatus();

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
