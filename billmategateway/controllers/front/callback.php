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
            return $this->respondWithError('Verifying payment data failed');
        }

        // Check if payment data is valid
        if (!$this->client->verifyCustomerData()) {
            return $this->respondWithError('Verifying customer data failed');
        }

        // Get cart with id from Billmate
        $this->cart_id = $this->client->getCartId();

        $this->context->cart = new Cart($this->cart_id);

        // Get cart_delivery_option options
        $cartDeliveryOption = $this->context->cart->getDeliveryOption();

        // Get cart or fail
        if (!$this->context->cart) {
            return $this->respondWithError('Loading cart failed');
        }

        // Get customer or fail
        if (!$customer = $this->customerHelper->createCustomer($this->context->cart)) {
            return $this->respondWithError('Loading customer failed');
        }

        $this->context->customer = $customer;
        $this->context->cart->id_customer = $customer->id;

        // If order exists, update it and show success page
        if ($order = $this->orderHelper->getOrderByCart($this->context->cart)) {
            $this->updateOrderStatus($order);

            // Show success page
            return $this->respondWithSuccess();
        }

        // Update customer with data from Billmate
        $this->context->customer = $this->customerHelper->updateCustomer(
            $this->context->customer,
            $this->client->getCustomer()
        );

        // Create billing address with data from Billmate
        $billingAddress = $this->customerHelper->createBillingAddress(
            $this->context->customer,
            $this->client->getCustomer()
        );

        // Create shipping address with data from Billmate
        $shippingAddress = $this->customerHelper->createShippingAddress(
            $this->context->customer,
            $this->client->getCustomer()
        );

        // Update cart with new ids
        $this->context->cart->id_address_invoice = (int)$billingAddress->id;
        $this->context->cart->id_address_delivery = (int)$shippingAddress->id;

        if (is_array($cartDeliveryOption)) {
            $carrierId = intval(current($cartDeliveryOption));
        } else {
            $carrierId = intval($cartDeliveryOption);
        }

        $this->context->cart->id_carrier = $carrierId;

        $cartDeliveryOption = array();
        $cartDeliveryOption[$this->context->cart->id_address_delivery] = sprintf('%s,', $this->context->cart->id_carrier);

        $this->context->cart->getOrderTotal(true, Cart::BOTH);
        $this->context->cart->setDeliveryOption($cartDeliveryOption);

        $this->context->cart->update();
        $this->context->cart->save();

        // Create new order or fail
        if (!$order = $this->createOrderFromCart()) {
             return $this->respondWithError('Creating order from cart failed');
        }

        // Send order reference to Billmate
        if (!$this->updatePaymentStatus()) {
            return $this->respondWithError('Updating payment failed');
        }

        // Show success page
        return $this->respondWithSuccess();
    }

    protected function respondWithError(...$arguments)
    {
        $message = !empty($arguments[0]) ? $arguments[0] : null;

        if (!empty($message)) {
            $this->logEvent('Callback failed: ' . $message);
        }

        header('HTTP/1.1 500 Internal Server Error');
        die('Error: ' . $message);
    }

    protected function respondWithSuccess(...$arguments)
    {
        header('HTTP/1.1 200 OK');
        die;
    }
}
