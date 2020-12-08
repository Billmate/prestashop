<?php

require_once(_PS_MODULE_DIR_ . 'billmategateway/library/Client.php');
require_once(_PS_MODULE_DIR_ . 'billmategateway/library/CustomerHelper.php');
require_once(_PS_MODULE_DIR_ . 'billmategateway/library/ModuleHandler.php');

class BillmategatewayCallbackModuleFrontController extends ModuleFrontController
{
    private $client;
    private $customerHelper;
    private $moduleHandler;
    private $method;
    private $module;

    public function __construct()
    {
        $this->method = Tools::getValue('method');
        $this->client = new Client;
        $this->customerHelper = new CustomerHelper;
        $this->moduleHandler = new ModuleHandler;
    }

    public function postProcess()
    {
        file_put_contents('callback.log', print_r($_POST ,1));
        die();

        if (!$this->module = $this->moduleHandler->getPaymentModule()) {
            $this->logEvent('Payment module was not valid');

            return $this->respondWithError();
        }

        if ($this->method == 'checkout' && !Configuration::get('PS_GUEST_CHECKOUT_ENABLED')) {
            Configuration::updateGlobalValue('PS_GUEST_CHECKOUT_ENABLED', 1);
        }

        if (!$this->client->verifyPayload()) {
            $this->logEvent('Payload data from Billmate not valid');

            return $this->respondWithError();
        }

        if (!$this->client->verifyPaymentData()) {
            $this->logEvent('Payment data from Billmate not valid');

            return $this->respondWithError();
        }

        // @todo: Verify customer data

        if (!$this->client->getCartId()) {
            $this->logEvent('Cart id from Billmate is missing');

            return $this->respondWithError();
        }

        if (!$this->client->getOrderId()) {
            $this->logEvent('Order id from Billmate is missing');

            return $this->respondWithError();
        }

        $this->logEvent('Recieved callback request for order #%s', $this->client->getOrderId());

        $cart = new Cart(
            $this->client->getCartId()
        );

        // @todo: $this->context->cart->orderExists()

        if ($order = $this->getOrderByCart($cart)) {
            if ($this->shouldUpdateOrder($order)) {
                $this->updateOrderStatus($order);

                $this->logEvent('Updated order status for order #%s', $order->id);
            }

            $this->logEvent('Order #%s already created, rendering the success page', $order->id);

            return $this->respondWithSuccess();
        }

        $customer = $this->customerHelper->getOrCreateCustomer($cart);

        $customer = $this->customerHelper->updateCustomer(
            $customer,
            $this->client->getCustomer()
        );

        $billingAddress = $this->customerHelper->createAddress(
            $customer,
            $this->client->getCustomer()
        );

        $shippingAddress = $this->customerHelper->createAddress(
            $customer,
            $this->client->getCustomer()
        );

        $cart->id_customer = $customer->id;
        $cart->id_address_invoice = $billingAddress->id;
        $cart->id_address_delivery = $shippingAddress->id;

        // @todo: fix shipping...

        $cart->update();
        $cart->save();

        if ($order = $this->createOrder($cart)) {
            $orderReference = (Configuration::get('BILLMATE_SEND_REFERENCE') == 'reference') ?
                $this->module->currentOrderReference :
                $this->module->currentOrder;

            $this->client->updatePayment($orderReference);
        }

        return $this->respondWithSuccess();
    }

    private function getPaymentMethodName()
    {
        $name = $this->module->displayName;

        if ($this->method == 'checkout' && !empty($this->client->getMethodName())) {
            $name = sprintf('%s (%s)', $name, $this->client->getMethodName());
        }

        return $name;
    }

    private function convertOrderStatus(string $status)
    {
        if ($status == 'Cancelled') {
            return Configuration::get('PS_OS_CANCELED');
        } elseif ($this->method == 'checkout') {
            return Configuration::get('BILLMATE_CHECKOUT_ORDER_STATUS');
        }

        return Configuration::get('B' . strtoupper($this->method) . '_ORDER_STATUS');
    }

    private function getOrderByCart(Cart $cart)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>')) {
            return Order::getByCartId($cart->id);
        }

        return new Order(
            Order::getOrderByCartId($cart->id)
        );
    }

    private function createOrder(Cart $cart)
    {
        return $this->module->validateOrder(
            $cart->id,
            $this->client->getTotalSum(),
            $this->getPaymentMethodName(),
            null,
            ['transaction_id' => $this->getTransactionId()],
            null,
            false
        );
    }

    private function updateOrderStatus(Order $order)
    {
        $orderStatus = $this->convertOrderStatus(
            $this->client->getStatus()
        );

        $this->createOrderHistory($order, $orderStatus);

        return true;
    }

    private function createOrderHistory($order, $status)
    {
        $orderHistory = new OrderHistory();

        try {
            $orderHistory->id_order = $order->id;
            $orderHistory->changeIdOrderState($status, $order->id, true);
            $orderHistory->add();
        } catch (Exception $e) {
            // @todo
        }

        return $orderHistory;
    }

    private function shouldUpdateOrder(Order $order)
    {
        return ($order->current_state == Configuration::get('BILLMATE_PAYMENT_PENDING') && !$this->client->isPending()) ? true : false;
    }

    private function logEvent(...$args)
    {
        PrestaShopLogger::addLog(
            sprintf($args)
        );
    }

    private function respondWithError()
    {

    }

    private function respondWithSuccess()
    {

    }
}
