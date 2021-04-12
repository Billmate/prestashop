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
            $this->logEvent('Failed to get post data');

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
            $this->logEvent('Failed to verify payload: Must be of type array');

            return false;
        } elseif (!empty($this->data['code']) || !empty($this->data['error'])) {
            $this->logEvent('Failed to verify payload: ' . $this->data['code'] . ':' . $this->data['error']);

            return false;
        } elseif (empty($this->data['data']['number']) || empty($this->data['data']['orderid'])) {
            $this->logEvent('Failed to verify payload: Number or OrderId is missing');

            return false;
        }

        return true;
    }

    public function verifyPaymentData()
    {
        try {
            $this->payment = $this->billmate->getPaymentinfo(array(
                'number' => $this->data['data']['number'],
            ));
        } catch (Exception $e) {
            $this->logEvent('Failed to verify payment data: '. $e->getMessage());

            return false;
        }

        return true;
    }

    public function verifyCustomerData()
    {
        if (!$customer = $this->getCustomer()) {
            $this->logEvent('Failed to verify customer data: Data is empty');

            return false;
        }

        if (empty($customer['Billing'])) {
            $this->logEvent('Failed to verify customer data: Billing data is empty');

            return false;
        }

        if (empty($customer['Billing']['email'])) {
            $this->logEvent('Failed to verify customer data: E-mail is empty');

            return false;
        }

        return true;
    }

    public function updatePayment($orderReference)
    {
        try {
            $this->billmate->updatePayment(array(
                'PaymentData' => array(
                    'number'  => $this->getNumber(),
                    'orderid' => $orderReference,
                )
            ));
        } catch (Exception $e) {
            $this->logEvent('Failed to update payment in client: '. $e->getMessage());

            return false;
        }

        return true;
    }

    public function cancelPayment()
    {
        try {
            $this->billmate->cancelPayment(array(
                'PaymentData' => array(
                    'number' => $this->getNumber(),
                ),
            ));
        } catch (Exception $e) {
            $this->logEvent('Failed to update payment in client: '. $e->getMessage());

            return false;
        }

        return true;
    }

    public function getCartId()
    {
        if (strrpos($this->getOrderId(), '-') === false) {
            return $this->getOrderId();
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

    public function getNumber()
    {
        return !empty($this->data['data']['number']) ?
            $this->data['data']['number'] :
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
        return !empty($this->data['data']['url']) ?
            $this->data['data']['url'] :
            null;
    }

    public function isPayed()
    {
        return in_array($this->getStatus(), array('Paid', 'Factoring', 'Service', 'Pending')) &&
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

    private function logEvent($message)
    {
        try {
            PrestaShopLogger::addLog(sprintf('[BILLMATE] %s', $message), 1);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
