<?php

require_once(_PS_MODULE_DIR_.'billmategateway/library/Common.php');

class Client
{
    public $useSSL = true;
    public $useDebug = false;

    private $billmate;
    private $data;
    private $payment;

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
        if (!$postData = $this->getJsonPostRequest()) {
            return false;
        }

        if (is_object($postData)) {
            $postData = json_decode(json_encode($postData), true);
        }

        if (is_array($postData)) {
            $postData['credentials'] = !empty($postData['credentials']) ? $postData['credentials'] : null;

            if (is_string($postData['credentials'])) {
                $postData['credentials'] = json_decode($postData['credentials'], true);
            }

            $postData['data'] = !empty($postData['data']) ? $postData['data'] : null;

            if (is_string($postData['data'])) {
                $postData['data'] = json_decode($postData['data'], true);
            }

            $this->data = $postData;
        }

        if (!is_array($this->data)) {
            // @todo: log...
            return false;
        } elseif (!empty($this->data['code']) || !empty($this->data['error'])) {
            // @todo: log...
            return false;
        } elseif (empty($this->data['data']['number']) || empty($this->data['data']['orderid'])) {
            // @todo: log...
            return false;
        }

        return true;
    }

    public function verifyPaymentData()
    {
        try {
            $this->payment = $this->billmate->getPaymentinfo([
                'number' => $this->data['data']['number'],
            ]);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function verifyCustomerData()
    {
        if (!$customer = $this->getCustomer()) {
            return false;
        }

        if (empty($customer['Billing'])) {
            return false;
        }

        if (empty($customer['Billing']['email'])) {
            return false;
        }

        if (empty($customer['Billing']['phone'])) {
            return false;
        }

        return true;
    }

    public function updatePayment($orderReference)
    {
        // @todo: try/catch...
        $this->billmate->updatePayment([
            'number'  => $this->getTransactionId(),
            'orderid' => $orderReference,
        ]);
    }

    public function getCartId()
    {
        if (strrpos($this->getOrderId(), '-') === false) {
            return null;
        }

        $parts = explode('-', $this->getOrderId());

        return !empty($parts[0]) ? $parts[0] : null;
    }

    public function getCustomer()
    {
        return !empty($this->payment['Customer']) ?
            $this->payment['Customer'] :
            null;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getDeliveryOption()
    {
        return $this->billmate->getDeliveryOption();
    }

    public function getHash()
    {
        return !empty($this->data['credentials']['hash']) ?
            $this->data['credentials']['hash'] :
            null;
    }

    public function getMethod()
    {
        return !empty($this->payment['PaymentData']['method']) ?
            $this->payment['PaymentData']['method'] :
            null;
    }

    public function getMethodName()
    {
        return !empty($this->payment['PaymentData']['method_name']) ?
            $this->payment['PaymentData']['method_name'] :
            null;
    }

    public function getOrderId()
    {
        return !empty($this->data['data']['orderid']) ?
            $this->data['data']['orderid'] :
            null;
    }

    public function getPaymentData()
    {
        return $this->payment;
    }

    public function getStatus()
    {
        return !empty($this->payment['PaymentData']['status']) ?
            $this->payment['PaymentData']['status'] :
            null;
    }

    public function getTotalSum()
    {
        return !empty($this->payment['Cart']['Total']['withtax']) ?
            ($this->payment['Cart']['Total']['withtax'] / 100) :
            null;
    }

    public function getTransactionId()
    {
        return !empty($this->payment['PaymentData']['number']) ?
            $this->payment['PaymentData']['number'] :
            null;
    }

    public function getPaymentUrl()
    {
        file_put_contents('data-payment.log', print_r($this->payment,1));
        return !empty($this->data['data']['url']) ?
            $this->data['data']['url'] :
            null;
    }

    public function isPayed()
    {
        return in_array($this->getStatus(), ['Paid', 'Factoring', 'Service', 'Pending']) &&
            (strrpos($this->getOrderId(), '-') === false) ? true : false;
    }

    public function isPending()
    {
        return ($this->getStatus() == 'Pending') ? true : false;
    }

    public function getJsonPostRequest()
    {
        if (!empty($_POST)) {
            return $_POST;
        }

        try {
            $json = file_get_contents('php://input');
        } catch (Exception $e) {
            return null;
        }

        return json_decode($json, false);
    }
}
