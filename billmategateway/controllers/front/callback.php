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
        $batch = date('YmdHis');

        file_put_contents("_{$batch}_callback_data.log", print_r($this->client->getData(),1));

        if ($this->method == 'checkout' && !Configuration::get('PS_GUEST_CHECKOUT_ENABLED')) {
            Configuration::updateGlobalValue('PS_GUEST_CHECKOUT_ENABLED', 1);
        }

        // Check if payment data is valid
        if (!$this->client->verifyPaymentData()) {
            return $this->respondWithError(01);
        }

        file_put_contents("_{$batch}_callback_payment.log", print_r($this->client->getPaymentData(),1));

        // Check if payment data is valid
        if (!$this->client->verifyCustomerData()) {
            return $this->respondWithError(02);
        }

        file_put_contents("_{$batch}_callback_customer.log", print_r($this->client->getCustomer(),1));

        // Get cart with id from Billmate
        $this->cart_id = $this->client->getCartId();

        $this->context->cart = new Cart($this->cart_id);

        // Get cart_delivery_option options
        //$cartDeliveryOption = $this->context->cart->getDeliveryOption();

        // Get cart or fail
        if (!$this->context->cart) {
            return $this->respondWithError(03);
        }

        // Get customer or fail
        if (!$customer = $this->customerHelper->createCustomer($this->context->cart)) {
            return $this->respondWithError(04);
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

        //if (is_array($cartDeliveryOption)) {
        //    $cartDeliveryOption = current($cartDeliveryOption);
        //}

        //$this->actionSetShipping($cartDeliveryOption);

        $this->context->cart->update();
        $this->context->cart->save();

        // Create new order or fail
        if (!$order = $this->createOrderFromCart($this->context->cart)) {
             return $this->respondWithError(05);
        }

        // Send order reference to Billmate
        if (!$this->updatePaymentStatus()) {
            return $this->respondWithError(06);
        }

        // Show success page
        return $this->respondWithSuccess();
    }

    public function actionSetShipping($cartDeliveryOption)
    {
        try {
            if (!is_array($cartDeliveryOption)) {
                $cartDeliveryOption = array(
                    $this->context->cart->id_address_delivery => $cartDeliveryOption
                );
            }

            $validateOptionResult = $this->validateDeliveryOption($cartDeliveryOption);

            if ($validateOptionResult) {
                if (version_compare(_PS_VERSION_,'1.7','>=') && !(version_compare(_PS_VERSION_,'1.7.5','>='))) {
                    $deliveryOption =  $cartDeliveryOption;
                    $realOption = array();

                    foreach ($deliveryOption as $key => $value){
                        $realOption[$key] = Cart::desintifier($value);
                    }

                    $this->context->cart->setDeliveryOption($realOption);
                } else {
                    $this->context->cart->setDeliveryOption($cartDeliveryOption);
                }
            }

            $this->context->cart->update();
            $this->context->cart->save();

            CartRule::autoRemoveFromCart($this->context);
            CartRule::autoAddToCart($this->context);
        } catch(Exception $e){
            die($e->getMessage());
            return false;
        }

        return true;
    }

    protected function validateDeliveryOption($cartDeliveryOption)
    {
        if (!is_array($cartDeliveryOption)) {
            return false;
        }

        foreach ($cartDeliveryOption as $option) {
            if (!preg_match('/(\d+,)?\d+/', $option)) {
                return false;
            }
        }

        return true;
    }

    protected function respondWithError(...$arguments)
    {
        $message = !empty($arguments[0]) ? $arguments[0] : null;

        header('HTTP/1.1 500 Internal Server Error');
        die($message);
    }

    protected function respondWithSuccess(...$arguments)
    {
        header('HTTP/1.1 200 OK');
        die;
    }
}
