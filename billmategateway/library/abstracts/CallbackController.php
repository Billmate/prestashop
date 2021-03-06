<?php

require_once(_PS_MODULE_DIR_ . 'billmategateway/library/services/Payload.php');
require_once(_PS_MODULE_DIR_ . 'billmategateway/library/services/Resolver.php');

require_once(_PS_MODULE_DIR_ . 'billmategateway/library/helpers/CustomerHelper.php');
require_once(_PS_MODULE_DIR_ . 'billmategateway/library/helpers/OrderHelper.php');

abstract class CallbackController extends ModuleFrontController
{
    public $client;
    public $method;
    public $module;
    public $request;

    protected $payload;
    protected $resolver;
    protected $customerHelper;
    protected $orderHelper;

    public function __construct()
    {
        parent::__construct();

        $this->payload = new Payload;
        $this->resolver = new Resolver;

        $this->customerHelper = new CustomerHelper;
        $this->orderHelper = new OrderHelper;

        if (!$this->verify()) {
            return $this->respondWithError();
        }
    }

    protected function verify()
    {
        if (!$this->resolver->verify()) {
            return false;
        }

        $this->method = $this->resolver->getMethod();
        $this->module = $this->resolver->getModule();

        if (!$this->payload->verify()) {
            return false;
        }

        $this->client = $this->payload->getClient();

        return true;
    }

    protected function createOrderFromCart()
    {
        try {
            $orderTotal = $this->client->getTotalSum();
            $methodName = $this->getPaymentMethodName();

            $orderStatus = $this->orderHelper->convertOrderStatus(
                $this->method,
                $this->client->getStatus()
            );

            $extraData = array(
                'transaction_id' => $this->client->getNumber()
            );

            if (in_array($this->client->getMethod(), array(1, 2))) {
                $this->module = $this->resolver->getInvoiceMethod();
            }

            $order = $this->module->validateOrder($this->context->cart->id, $orderStatus, $orderTotal, $methodName, null, $extraData, null, false, $this->context->customer->secure_key);
        } catch (Exception $e) {
            $this->logEvent('Failed to create order from cart: ' . $e->getMessage());

            return null;
        }

        return $order;
    }

    protected function updateOrderStatus(OrderCore $order)
    {
        if (!$this->orderHelper->shouldUpdateOrder($order)) {
            return false;
        }

        $orderStatus = $this->orderHelper->convertOrderStatus(
            $this->method,
            $this->client->getStatus()
        );

        try {
            $this->orderHelper->updateOrderStatus($order, $orderStatus);
        } catch (Exception $e) {
            $this->logEvent('Failed to update order status: ' . $e->getMessage());

            return false;
        }

        return true;
    }

    protected function updatePaymentStatus()
    {
        try {
            $this->client->updatePayment(
                $this->getOrderReference()
            );
        } catch (Exception $e) {
            $this->logEvent('Failed to update payment status: ' . $e->getMessage());

            return false;
        }

        return true;
    }

    private function getPaymentMethodName()
    {
        $name = $this->module->displayName;
        $method = $this->client->getMethodName();

        if ($this->method == 'checkout' && !empty($method)) {
            $name = sprintf('%s (%s)', $name, $method);
        }

        return $name;
    }

    protected function getOrderReference()
    {
        return (Configuration::get('BILLMATE_SEND_REFERENCE') == 'reference') ?
            $this->module->currentOrderReference :
            $this->module->currentOrder;
    }

    protected function respondWithError($value)
    {
        header('HTTP/1.1 500 Internal Server Error');
        exit();
    }

    protected function respondWithSuccess($value)
    {
        header('HTTP/1.1 200 OK');
        exit();
    }

    protected function logEvent($message)
    {
        try {
            PrestaShopLogger::addLog(sprintf('[BILLMATE] %s', $message), 1);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
