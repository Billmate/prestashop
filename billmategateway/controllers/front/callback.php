<?php

require_once(_PS_MODULE_DIR_ . 'billmategateway/library/abstracts/CallbackController.php');

class BillmategatewayCallbackModuleFrontController extends CallbackController
{
    public $request = 'callback';

    public function __construct()
    {
        parent::__construct();
    }

    public function getInvoiceProductId()
    {
        $productId = Db::getInstance()->getValue(
            'SELECT id_product FROM '._DB_PREFIX_.'product WHERE reference = "billmate_invoice_fee"'
        );

        return $productId;
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
        //$cartDeliveryOption = $this->context->cart->getDeliveryOption();

        // Get cart or fail
        if (!$this->context->cart) {
            return $this->respondWithError('Loading cart failed');
        }

        /*if (Configuration::get('BINVOICE_FEE') > 0) {
            $productId = $this->getInvoiceProductId();

            $product = new Product($productId);
            $product->price = Configuration::get('BINVOICE_FEE');
            $product->update();

            if (!empty($productId) && !$this->context->cart->containsProduct($productId)) {
                $this->context->cart->updateQty(1, $productId);
                $this->context->cart->getPackageList(true);
            }
        }*/

        // Get customer or fail
        if (!$customer = $this->customerHelper->createCustomer($this->context->cart->id_customer)) {
            return $this->respondWithError('Loading customer failed');
        }

        $this->context->cart->id_customer = $customer->id;
        $this->context->cart->secure_key = $customer->secure_key;

        // If order exists, update it and show success page
        if ($order = $this->orderHelper->getOrderByCart($this->context->cart)) {
            $this->updateOrderStatus($order);

            // Show success page
            return $this->respondWithSuccess();
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

        if (is_array($cartDeliveryOption)) {
            $carrierId = intval(current($cartDeliveryOption));
        } else {
            $carrierId = intval($cartDeliveryOption);
        }

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
