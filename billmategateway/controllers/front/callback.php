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

        // Check if customer data is valid
        if (!$this->client->verifyCustomerData()) {
            return $this->respondWithError('Verifying customer data failed');
        }

        // Get cart with id from Billmate
        $this->cart_id = $this->client->getCartId();

        // Load cart
        $this->context->cart = new Cart($this->cart_id);

        // Get cart or fail
        if (!$this->context->cart) {
            return $this->respondWithError('Loading cart failed');
        }

        // Get customer or fail
        if (!$customer = $this->customerHelper->createCustomer($this->context->cart->id_customer)) {
            return $this->respondWithError('Loading customer failed');
        }

        $this->context->cart->id_customer = $customer->id;
        $this->context->cart->secure_key = $customer->secure_key;

        // If order exists, update it and show success page
        if ($order = $this->orderHelper->getOrderByCart($this->context->cart)) {

            $payment = OrderPayment::getByOrderReference($order->reference);

            if (is_array($payment)) {
                $payment = $payment[0];
            }

            $paymentNumber = $this->client->getNumber();

            if (!empty($payment->transaction_id) && $payment->transaction_id != $paymentNumber) {
                PrestaShopLogger::addLog(
                    sprintf('Trying to cancel payment (%s) because an order already exists.', $paymentNumber)
                );

                $logMessage = ($this->client->cancelPayment()) ?
                    sprintf('Cancellation for payment (%s) was successful.', $paymentNumber) :
                    sprintf('Cancellation for payment (%s) failed.', $paymentNumber);

                PrestaShopLogger::addLog($logMessage);
            } else {
                $this->updateOrderStatus($order);
            }

            // Show success page
            return $this->respondWithSuccess(true);
        }

        // Update customer with data from Billmate
        $this->context->customer = $this->customerHelper->updateCustomer(
            $customer,
            $this->client->getCustomer()
        );

        // Create billing address with data from Billmate
        $billingAddress = $this->customerHelper->createBillingAddress(
            $customer,
            $this->client->getCustomer()
        );

        // Update cart with new customer
        $this->context->customer = $customer;

        // Update cart with new ids
        $this->context->cart->id_address_invoice = (int)$billingAddress->id;
        $this->context->cart->id_address_delivery = (int)$billingAddress->id;
        $this->context->cart->update();

        $cartDeliveryOption = array(
            $this->context->cart->id_address_delivery => $this->context->cart->id_carrier . ',',
        );

        $this->context->cart->delivery_option = json_encode($cartDeliveryOption);

        $this->context->cart->update();

        if (version_compare(_PS_VERSION_,'1.7','<')) {
            $this->context->cart = new Cart($this->context->cart->id);
        }

        // Create new order or fail
        if (!$order = $this->createOrderFromCart()) {
             return $this->respondWithError('Creating order from cart failed');
        }

        // Send order reference to Billmate
        if (!$this->updatePaymentStatus()) {
            return $this->respondWithError('Updating payment failed');
        }

        // Show success page
        return $this->respondWithSuccess(true);
    }

    protected function respondWithError($value)
    {
        $message = !empty($value) ? $value : null;

        if (!empty($message) && is_string($message)) {
            $this->logEvent('Callback failed: ' . $message);
        }

        header('HTTP/1.1 500 Internal Server Error');
        die('Error: ' . $message);
    }

    protected function respondWithSuccess($value)
    {
        header('HTTP/1.1 200 OK');
        die;
    }
}
