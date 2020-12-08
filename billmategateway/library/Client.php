<?php

require_once(_PS_MODULE_DIR_.'billmategateway/library/Common.php');

class Client
{
    public $useSSL = true;
    public $useDebug = false;

    private $billmate;
    private $data;

    public function __construct()
    {
        $this->billmate = Common::getBillmate(
            Configuration::get('BILLMATE_ID'),
            Configuration::get('BILLMATE_SECRET'),
            Configuration::get('BILLMATE_CHECKOUT_TESTMODE'),
            $this->useSSL,
            $this->useDebug
        );
    }

    public function verifyPayload()
    {
        $postData = $this->getJsonPostRequest();

        $this->data = $this->billmate->verify_hash(
            !empty($postData) ?$postData : $_GET
        );

        if (!is_object($this->data)) {
            // @todo: log...
            return false;
        } elseif (!empty($this->data->code) || !empty($this->data->error)) {
            // @todo: log...
            return false;
        } elseif (empty($this->data->data->number) || empty($this->data->data->orderid)) {
            // @todo: log...
            return false;
        }

        return true;
    }

    private function verifyPaymentData()
    {
        $this->payment = $this->billmate->getPaymentinfo([
            'number' => $this->data->data->number,
        ]);

        return true;
    }

    public function updatePayment($orderReference)
    {
        $this->billmate->updatePayment([
            'number'  => $this->getTransactionId(),
            'orderid' => $orderReference,
        ]);
    }

    public function getCartId()
    {
        $parts = explode('-', $this->getOrderId());

        return !empty($parts[0]) ? $parts[0] : null;
    }

    public function getCustomer()
    {
        return !empty($this->data['customer']) ?
            $this->data['customer'] :
            null;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getMethodName()
    {
        return !empty($this->payment['PaymentData']['method_name']) ?
            $this->payment['PaymentData']['method_name'] :
            null;
    }

    public function getOrderId()
    {
        return !empty($this->data['orderid']) ?
            $this->data['orderid'] :
            null;
    }

    public function getStatus()
    {
        return !empty($this->payment['status']) ?
            $this->payment['status'] :
            null;
    }

    public function getTotalSum()
    {
        return !empty($this->data['Cart']['Total']['withtax']) ?
            ($this->data['Cart']['Total']['withtax'] / 100) :
            null;
    }

    public function getTransactionId()
    {
        return !empty($this->payment['PaymentData']['order']['number']) ?
            $this->payment['PaymentData']['order']['number'] :
            null;
    }

    public function isPending()
    {
        return ($this->client->getStatus() == 'Pending') ? true : false;
    }

    public function getJsonPostRequest()
    {
        if (!empty($_POST)) {
            return $_POST;
        }

        $json = file_get_contents('php://input');

        return json_decode($json, false);
    }
}
