<?php

require_once(_PS_MODULE_DIR_ . 'billmategateway/library/services/Payload.php');
require_once(_PS_MODULE_DIR_ . 'billmategateway/library/services/Resolver.php');

require_once(_PS_MODULE_DIR_ . 'billmategateway/library/helpers/CartHelper.php');
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
    protected $cartHelper;
    protected $customerHelper;
    protected $orderHelper;

    public function __construct()
    {
        parent::__construct();

        $this->payload = new Payload;
        $this->resolver = new Resolver;

        $this->cartHelper = new CartHelper;
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

    protected function createOrderFromCart(Cart $cart)
    {
        try {
            $orderTotal = $this->client->getTotalSum();
            $methodName = $this->getPaymentMethodName();

            $orderStatus = $this->orderHelper->convertOrderStatus(
                $this->method,
                $this->client->getStatus()
            );

            $extraData = [
                'transaction_id' => $this->client->getTransactionId()
            ];

            $order = $this->module->validateOrder($cart->id, $orderStatus, $orderTotal, $methodName, null, $extraData, null, false);

            // @todo: log...

        } catch (Exception $e) {
            die($e->getMessage());
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
            return false;
        }

        return true;
    }

    private function getPaymentMethodName()
    {
        $name = $this->module->displayName;

        if ($this->method == 'checkout' && !empty($this->client->getMethodName())) {
            $name = sprintf('%s (%s)', $name, $this->client->getMethodName());
        }

        return $name;
    }

    protected function getOrderReference()
    {
        return (Configuration::get('BILLMATE_SEND_REFERENCE') == 'reference') ?
            $this->module->currentOrderReference :
            $this->module->currentOrder;
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

    protected function logEvent(...$args)
    {
        try {
            PrestaShopLogger::addLog(
                sprintf($args)
            );
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
