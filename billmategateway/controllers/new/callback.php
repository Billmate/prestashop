<?php

class BillmategatewayAcceptModuleFrontController extends ModuleFrontController
{
    // @todo: Move to helpers...
    // @todo: Process normal payments
    private function initPaymentModule(string $fileName, string $className)
    {
        $filePath = str_replace('//', '/', sprintf('%s/billmategateway/methods/%s', _PS_MODULE_DIR_, $className));

        if (!file_exists($filePath)) {
            throw new Exception('');
        }

        if (!class_exists($className)) {
            throw new Exception('');
        }

        return new $className;
    }

    private function getPaymentModule()
    {
        switch ($this->method) {
            case 'bankpay':
                return $this->initPaymentModule('Bankpay.php', BillmateMethodBankpay::class);

            case 'cardpay':
                return $this->initPaymentModule('Cardpay.php', BillmateMethodCardpay::class);

            case 'checkout':
                return $this->initPaymentModule('Checkout.php', BillmateMethodCheckout::class);

            case 'invoice':
                return $this->initPaymentModule('Invoice.php', BillmateMethodInvoice::class);

            case 'partpay':
                return $this->initPaymentModule('Partpay.php', BillmateMethodPartpay::class);

            default:
                return null;
        }
    }

    public function initBillmateGateway()
    {
        $useSSL = true;
        $useDebug = false;

        $this->billmate = Common::getBillmate(
            Configuration::get('BILLMATE_ID'),
            Configuration::get('BILLMATE_SECRET'),
            Configuration::get('BILLMATE_CHECKOUT_TESTMODE'),
            $useSSL,
            $useDebug
        );
    }

    private function verifyDataFromBillmate()
    {
        $data = $this->billmate->verify_hash(
            !empty($_POST) ? $_POST : $_GET
        );

        if (!is_array($data)) {
            throw new Exception('');
        } elseif (!empty($data['code']) || !empty($data['error'])) {
            throw new Exception('');
        } elseif (empty($data['number']) || empty($data['orderid'])) {
            throw new Exception('');
        }

        return $data;
    }

    private function extractCartId(string $value)
    {
        $parts = explode('-', $value);

        return !empty($parts[0]) ? $parts[0] : $value;
    }

    private function getPaymentDataFromBillmate(int $number)
    {
        return $this->billmate->getPaymentinfo([
            'number' => $number,
        ]);
    }

    private function getOrderByCartId(int $cartId)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>')) {
            return Order::getByCartId($cartId);
        }

        return new Order(
            Order::getOrderByCartId($cartId)
        );
    }

    private function getPrettyDisplayName()
    {
        $displayName = $this->module->displayName;
        if ($this->method == 'checkout') {
            /** When checkout, check for selected payment method found in $paymentInfo.PaymentData.method_name */
            if (isset($paymentInfo['PaymentData']['method_name']) AND $paymentInfo['PaymentData']['method_name'] != '') {
                $displayName = $displayName . ' (' . $paymentInfo['PaymentData']['method_name'] . ')';
            }
        }

        return;
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

    private function getPaymentStatus(string $status)
    {
        if ($status === 'Cancelled') {
            return Configuration::get('PS_OS_CANCELED');
        } elseif ($this->method === 'checkout') {
            return Configuration::get('BILLMATE_CHECKOUT_ORDER_STATUS');
        }

        return Configuration::get('B' . strtoupper($this->method) . '_ORDER_STATUS');
    }

    private function createCustomer(array $data)
    {
        $customer = new Customer();
        $customer->firstname = !empty($data['firstname']) ? $data['firstname'] : '';
        $customer->lastname = !empty($data['lastname']) ? $data['lastname'] : '';
        $customer->company = !empty($data['company']) ? $data['company'] : '';
        $customer->email = !empty($data['email']) ? $data['email'] : '';

        $customer->newsletter = 0;
        $customer->optin = 0;
        $customer->is_guest = 1;
        $customer->active = 1;

        $customer->add();

        return $customer;
    }

    private function createAddress(Customer $customer, array $data)
    {
        $address = new Address();
        $address->id_customer = $customer->id;
        $address->firstname = !empty($data['firstname']) ? $data['firstname'] : '';
        $address->lastname = !empty($data['lastname']) ? $data['lastname'] : '';
        $address->company = !empty($data['company']) ? $data['company'] : '';
        $address->phone = !empty($data['phone']) ? $data['phone'] : '';
        $address->address1 = !empty($data['street']) ? $data['street'] : '';
        $address->address2 = !empty($data['street2']) ? $data['street2'] : '';
        $address->postcode = !empty($data['zip']) ? $data['zip'] : '';
        $address->city = !empty($data['city']) ? $data['city'] : '';
        $address->country = !empty($data['country']) ? $data['country'] : '';

        if ($address->country) {
            $address->id_country = Country::getByIso($address->country);
        }

        $address->save();

        return $address;
    }

    private function getExistingCustomer(Cart $cart)
    {
        return new Customer($cart->id_customer);
    }

    private function getPaymentTotal(array $data)
    {
        $total = !empty($result['Cart']['Total']['withtax']) ? $result['Cart']['Total']['withtax'] : 0;
        $total = $total / 100;

        return $total;
    }

    public function postProcess()
    {
        $this->method = Tools::getValue('method');

        // Make sure guest checkout is active when using Billmate Checkout
        if ($this->method == 'checkout' && !Configuration::get('PS_GUEST_CHECKOUT_ENABLED')) {
            Configuration::updateGlobalValue('PS_GUEST_CHECKOUT_ENABLED', 1);
        }

        $data = $this->verifyDataFromBillmate();
        $cartId = $this->extractCartId($data['orderid']);
        $payment = $this->getPaymentDataFromBillmate($data['number']);

        // @todo: $this->context->cart->orderExists()

        PrestaShopLogger::addLog(
            sprintf('Recieved accept request for order #', $payment['PaymentData']['orderid'])
        );

        $cart = new Cart($cartId);

        $paymentStatus = $this->getPaymentStatus($payment['status']);
        $paymentTotal = $this->getPaymentTotal($payment);
        $paymentTypeName = $this->getPrettyDisplayName();

        if ($order = $this->getOrderByCartId($cartId)) {
            if ($order->current_state == Configuration::get('BILLMATE_PAYMENT_PENDING') && $payment['status'] != 'Pending') {
                $orderHistory = $this->createOrderHistory($order, $paymentStatus);
            }

            return $this->respondWithSuccess();
        }

        $customer = !empty($cart->id_customer) ?
            $this->getExistingCustomer($cart) :
            $this->createCustomer();

        // @todo: check if customer has addresses...

        $billingAddress = $this->createAddress($customer);
        $shippingAddress = $this->createAddress($customer);

        $cart->id_address_invoice = $billingAddress->id;
        $cart->id_address_delivery = $shippingAddress->id;

        $cartDeliveryOptions = $cart->getDeliveryOption();

        // @todo: fix shipping...

        $cart->update();
        $cart->save();

        $orderExtra = [
            'transaction_id' => $payment['PaymentData']['order']['number']
        ];

        $order = $this->createOrder($cart->id, $paymentTotal, $paymentTypeName, null, $orderExtra, null, false);

        $this->billmate->updatePayment([
            'number' => $payment['PaymentData']['order']['number'],
            'orderid' => (Configuration::get('BILLMATE_SEND_REFERENCE') == 'reference') ? $this->module->currentOrderReference : $this->module->currentOrder,
        ]);

        return $this->respondWithSuccess();
    }
}
