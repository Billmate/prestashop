<?php
    class BillmategatewayAcceptModuleFrontController extends ModuleFrontController
    {
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
            $useTestMode = boolval($this->module->testMode);

            $this->billmate = Common::getBillmate(
                Configuration::get('BILLMATE_ID'),
                Configuration::get('BILLMATE_SECRET'),
                $useTestMode,
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

            PrestaShopLogger::addLog(
                sprintf('Recieved accept request for order #', $payment['PaymentData']['orderid'])
            );

            $cart = new Cart($cartId);
            $customer = new Customer($cart->id_customer);

            if ($order = $this->getOrderByCartId($cartId)) {
                $transactions = OrderPayment::getByOrderReference($order->reference);
                $transaction = isset($transactions[0]) ? $transactions[0] : null;

                if (!empty($transaction->transaction_id) && $transaction->transaction_id != $data['number']) {
                    // @todo
                } else {
                    // @todo
                }
            }

            Tools::redirect('index.php?controller=billmate-order-confirmation');
            die;


                $displayName = $this->module->displayName;
                if ($this->method == 'checkout') {
                    /** When checkout, check for selected payment method found in $paymentInfo.PaymentData.method_name */
                    if (isset($paymentInfo['PaymentData']['method_name']) AND $paymentInfo['PaymentData']['method_name'] != '') {
                        $displayName = $displayName . ' (' . $paymentInfo['PaymentData']['method_name'] . ')';
                    }
                }


                if (!isset($data['code']) && !isset($data['error'])) {
                    if (!isset($paymentInfo['code']) AND $this->method != 'checkout') {
                        switch ($paymentInfo['PaymentData']['method']) {
                            case '4':
                                $this->method = 'partpay';
                                break;
                            case '8':
                                $this->method = 'cardpay';
                                break;
                            case '16':
                                $this->method = 'bankpay';
                                break;
                            default:
                                $this->method = 'invoice';
                                break;

                        }
                    }

                    $class_file = _PS_MODULE_DIR_ . 'billmategateway/methods/' . Tools::ucfirst($this->method) . '.php';
                    require_once($class_file);
                    $class = "BillmateMethod" . Tools::ucfirst($this->method);
                    $this->module = new $class;

                    $lockfile = _PS_CACHE_DIR_ . $data['orderid'];
                    $processing = file_exists($lockfile);
                    if ($this->context->cart->orderExists() || $processing) {
                        $order_id = 0;
                        if ($processing)
                            $order_id = $this->checkOrder($this->context->cart->id);
                        else
                            $order_id = Order::getOrderByCartId($this->context->cart->id);

                        $orderObject = new Order($order_id);
                        if ($orderObject->current_state == Configuration::get('BILLMATE_PAYMENT_PENDING') && $data['status'] != 'Pending') {
                            $orderHistory = new OrderHistory();

                            $status_key = 'B' . strtoupper($this->method) . '_ORDER_STATUS';
                            if ($this->method == 'checkout') {
                                $status_key = 'BILLMATE_CHECKOUT_ORDER_STATUS';
                            }

                            $status = Configuration::get($status_key);

                            $status = ($data['status'] == 'Cancelled') ? Configuration::get('PS_OS_CANCELED') : $status;
                            $orderHistory->id_order = (int)$orderObject->id;
                            $orderHistory->changeIdOrderState($status, (int)$orderObject->id, true);
                            $orderHistory->add();
                        }

                        if (isset($this->context->cookie->billmatepno)) {
                            unset($this->context->cookie->billmatepno);
                        }

                        $realModuleId = Module::getModuleIdByName($this->module->name);

                         Tools::redirect('index.php?controller=order-confirmation' .
                            '&id_cart=' . (int)$this->context->cart->id .
                            '&id_module=' . (int)$realModuleId .
                            '&id_order=' . (int)$order_id .
                            '&key=' . $customer->secure_key .
                            '&token=1'
                        );
                        die;
                    } else {


                        if ($paymentInfo['PaymentData']['method'] == '1' || '2' == $paymentInfo['PaymentData']['method']) {
                            /** Paid with invoice */
                            $this->paymentClass = 'invoice';
                            $class_file = _PS_MODULE_DIR_ . 'billmategateway/methods/' . Tools::ucfirst($this->paymentClass) . '.php';
                            require_once($class_file);
                            $class = "BillmateMethod" . Tools::ucfirst($this->paymentClass);
                            $this->module = new $class;
                        }


                        $return = array();
                        $result = $paymentInfo;

                        if (!isset($result['code']) && (isset($result['PaymentData']['order']['number']) && is_numeric($result['PaymentData']['order']['number']) && $result['PaymentData']['order']['number'] > 0)) {

                            $status = ($this->method == 'invoice') ? Configuration::get('BINVOICE_ORDER_STATUS') : Configuration::get('BPARTPAY_ORDER_STATUS');
                            $status = ($this->method == 'invoiceservice') ? Configuration::get('BINVOICESERVICE_ORDER_STATUS') : $status;

                            if ($this->method == 'checkout') {
                                $status_key = 'BILLMATE_CHECKOUT_ORDER_STATUS';
                                $status = Configuration::get($status_key);
                            }
                            $status = ($result['PaymentData']['order']['status'] == 'Pending') ? Configuration::get('BILLMATE_PAYMENT_PENDING') : $status;

                            if (Validate::isLoadedObject($this->context->cart) && $this->context->cart->OrderExists() == false) {
                                $extra = array('transaction_id' => $result['PaymentData']['order']['number']);
                                $total = $this->context->cart->getOrderTotal();
                                $total = $result['Cart']['Total']['withtax'];
                                $total = $total / 100;
                                $customer = new Customer((int)$this->context->cart->id_customer);
                                $orderId = 0;

                                $this->module->validateOrder((int)$this->context->cart->id,
                                    $status,
                                    $total,
                                    $displayName,
                                    null, $extra, null, false, $customer->secure_key);
                                $orderId = $this->module->currentOrder;

                                $values = array();
                                $values['PaymentData'] = array(
                                    'number' => $result['PaymentData']['order']['number'],
                                    'orderid' => (Configuration::get('BILLMATE_SEND_REFERENCE') == 'reference') ? $this->module->currentOrderReference : $this->module->currentOrder
                                );

                                $billmate->updatePayment($values);
                            }

                            $realModuleId = Module::getModuleIdByName($this->module->name);

                            $url = 'index.php?controller=order-confirmation' .
                                '&id_cart=' . (int)$this->context->cart->id .
                                '&id_module=' . (int)$realModuleId .
                                '&id_order=' . $this->module->currentOrder .
                                '&key=' . $customer->secure_key .
                                '&token=2';

                            $return['success'] = true;
                            $return['redirect'] = $url;
                            Common::unsetCartCheckoutHash();
                        } else {
                            if (isset($result['code']) AND in_array($result['code'], array(2401, 2402, 2403, 2404, 2405))) {
                                if (is_array($result)) {
                                    // Handle error message
                                    // die(Tools::jsonEncode($result));
                                }
                            }
                            $_message = (isset($result['message'])) ? $result['message'] : '';
                            $return = array('success' => false, 'content' => utf8_encode($_message));
                        }
                    }

                    file_put_contents($lockfile, 1);

                    $cart_delivery_option = $this->context->cart->getDeliveryOption();

                    if (
                        $this->context->cart->id_customer == 0
                        || $this->context->cart->id_address_invoice == 0
                        || $this->context->cart->id_address_delivery == 0
                    ) {
                        $result = $this->fetchCheckout();
                        if (isset($result['Customer'])) {
                            $customer = $result['Customer'];
                            $address = $customer['Billing'];
                            $country = isset($customer['Billing']['country']) ? $customer['Billing']['country'] : 'SE';
                            $bill_phone = isset($customer['Billing']['phone']) ? $customer['Billing']['phone'] : '';
                        }
                    }

                    /** Create customer when missing */
                    if ($this->context->cart->id_customer == 0) {
                        $customerObject = new Customer();
                        $password = Tools::passwdGen(8);
                        $customerObject->firstname = !empty($address['firstname']) ? $address['firstname'] : '';
                        $customerObject->lastname = !empty($address['lastname']) ? $address['lastname'] : '';
                        $customerObject->company = isset($address['company']) ? $address['company'] : '';
                        $customerObject->email = isset($address['email']) ? $address['email'] : '';
                        $customerObject->passwd = Tools::encrypt($password);
                        $customerObject->id_default_group = (int)(Configuration::get('PS_GUEST_GROUP', null, $this->context->cart->id_shop));
                        $customerObject->id_gender = 9;
                        $customerObject->newsletter = 0;
                        $customerObject->optin = 0;
                        $customerObject->is_guest = 1;
                        $customerObject->active = 1;
                        $customerObject->add();

                        if (version_compare(_PS_VERSION_, '1.7.0.0', '>')) {
                            $this->context->cookie->id_customer = (int)$customerObject->id;
                            $this->context->cookie->customer_lastname = $customerObject->lastname;
                            $this->context->cookie->customer_firstname = $customerObject->firstname;
                            $this->context->cookie->passwd = $customerObject->passwd;
                            $this->context->cookie->email = $customerObject->email;
                        }

                        $this->context->customer = $customerObject;

                        $this->context->cart->id_customer = $customerObject->id;
                        $this->context->cart->secure_key = $customerObject->secure_key;
                        $this->context->cart->save();

                        try {
                            $query = sprintf(
                                'UPDATE %s_cart SET id_customer = "%s", secure_key = "%s" WHERE id_cart= ""',
                                _DB_PREFIX_,
                                $customerObject->id,
                                $customerObject->secure_key,
                                $this->context->cart->id
                            );

                            Db::getInstance()->execute($query);
                        } catch (Exception $e) {
                            // Ignore
                        }
                    }

                    /** Create billing/shipping address when missing */
                    if ($this->context->cart->id_address_invoice == 0 || $this->context->cart->id_address_delivery == 0) {

                        $_customer = new Customer($this->context->cart->id_customer);
                        $customer_addresses = $_customer->getAddresses($this->context->language->id);

                        if (count($customer_addresses) == 1) {
                            $customer_addresses[] = $customer_addresses;
                        }

                        $matched_address_id = false;
                        foreach ($customer_addresses as $customer_address) {
                            if (isset($customer_address['address1'])) {
                                $billing = new Address($customer_address['id_address']);

                                $user_bill = $billing->firstname . ' ' . $billing->lastname . ' ' . $billing->company;
                                $company = isset($address['company']) ? $address['company'] : '';
                                $api_name = $address['firstname'] . ' ' . $address['lastname'] . ' ' . $company;

                                if (Common::matchstr($user_bill, $api_name) && Common::matchstr($customer_address['address1'], $address['street']) &&
                                    Common::matchstr($customer_address['postcode'], $address['zip']) &&
                                    Common::matchstr($customer_address['city'], $address['city']) &&
                                    Common::matchstr(Country::getIsoById($customer_address['id_country']), $address['country']))

                                    $matched_address_id = $customer_address['id_address'];
                            } else {
                                foreach ($customer_address as $c_address) {
                                    $billing = new Address($c_address['id_address']);

                                    $user_bill = $billing->firstname . ' ' . $billing->lastname . ' ' . $billing->company;
                                    $company = isset($address['company']) ? $address['company'] : '';
                                    $api_name = $address['firstname'] . ' ' . $address['lastname'] . ' ' . $company;


                                    if (
                                        Common::matchstr($user_bill, $api_name) && Common::matchstr($c_address['address1'], $address['street'])
                                        && Common::matchstr($c_address['postcode'], $address['zip'])
                                        && Common::matchstr($c_address['city'], $address['city'])
                                        && Common::matchstr(Country::getIsoById($c_address['id_country']), $address['country'])
                                    ) {
                                        $matched_address_id = $c_address['id_address'];
                                    }
                                }
                            }
                        }

                        if (!$matched_address_id) {
                            $addressnew = new Address();
                            $addressnew->id_customer = (int)$this->context->cart->id_customer;

                            $addressnew->firstname = !empty($address['firstname']) ? $address['firstname'] : $billing->firstname;
                            $addressnew->lastname = !empty($address['lastname']) ? $address['lastname'] : $billing->lastname;
                            $addressnew->company = isset($address['company']) ? $address['company'] : '';

                            $addressnew->phone = $address['phone'];
                            $addressnew->phone_mobile = $address['phone'];

                            $addressnew->address1 = $address['street'];
                            $addressnew->postcode = $address['zip'];
                            $addressnew->city = $address['city'];
                            $addressnew->country = $address['country'];
                            $addressnew->alias = 'Bimport-' . date('Y-m-d');
                            $addressnew->id_country = Country::getByIso($address['country']);
                            $addressnew->save();

                            $matched_address_id = $addressnew->id;
                        }


                        $billing_address_id = $shipping_address_id = $matched_address_id;

                        if (
                            isset($customer['Shipping'])
                            && is_array($customer['Shipping'])
                            && isset($customer['Shipping']['firstname'])
                            && isset($customer['Shipping']['lastname'])
                            && isset($customer['Shipping']['street'])
                            && isset($customer['Shipping']['zip'])
                            && isset($customer['Shipping']['city'])
                            && $customer['Shipping']['firstname'] != ''
                            && $customer['Shipping']['lastname'] != ''
                            && $customer['Shipping']['street'] != ''
                            && $customer['Shipping']['zip'] != ''
                            && $customer['Shipping']['city'] != ''
                        ) {
                            $address = $customer['Shipping'];
                            file_put_contents($logfile, 'shippingAddress:' . print_r($address, true), FILE_APPEND);
                            file_put_contents($logfile, 'customerAddress:' . print_r($customer_addresses, true), FILE_APPEND);

                            $matched_address_id = false;
                            foreach ($customer_addresses as $customer_address) {
                                if (isset($customer_address['address1'])) {
                                    $billing = new Address($customer_address['id_address']);

                                    $user_bill = $billing->firstname . ' ' . $billing->lastname . ' ' . $billing->company;
                                    $company = isset($address['company']) ? $address['company'] : '';
                                    $api_name = $address['firstname'] . ' ' . $address['lastname'] . ' ' . $company;

                                    if (Common::matchstr($user_bill, $api_name) && Common::matchstr($customer_address['address1'], $address['street']) &&
                                        Common::matchstr($customer_address['postcode'], $address['zip']) &&
                                        Common::matchstr($customer_address['city'], $address['city']) &&
                                        Common::matchstr(Country::getIsoById($customer_address['id_country']), isset($address['country']) ? $address['country'] : $country)) {
                                        $matched_address_id = $customer_address['id_address'];
                                    }
                                } else {
                                    foreach ($customer_address as $c_address) {
                                        $billing = new Address($c_address['id_address']);

                                        $user_bill = $billing->firstname . ' ' . $billing->lastname . ' ' . $billing->company;
                                        $company = isset($address['company']) ? $address['company'] : '';
                                        $api_name = $address['firstname'] . ' ' . $address['lastname'] . ' ' . $company;


                                        if (Common::matchstr($user_bill, $api_name) && Common::matchstr($c_address['address1'], $address['street']) &&
                                            Common::matchstr($c_address['postcode'], $address['zip']) &&
                                            Common::matchstr($c_address['city'], $address['city']) &&
                                            Common::matchstr(Country::getIsoById($c_address['id_country']), isset($address['country']) ? $address['country'] : $country)
                                        ) {
                                            $matched_address_id = $c_address['id_address'];
                                        }
                                    }
                                }

                            }
                            if (!$matched_address_id) {
                                $address = $customer['Shipping'];
                                $addressshipping = new Address();
                                $addressshipping->id_customer = (int)$this->context->cart->id_customer;

                                $addressshipping->firstname = !empty($address['firstname']) ? $address['firstname'] : '';
                                $addressshipping->lastname = !empty($address['lastname']) ? $address['lastname'] : '';
                                $addressshipping->company = isset($address['company']) ? $address['company'] : '';

                                $addressshipping->phone = isset($address['phone']) ? $address['phone'] : $bill_phone;
                                $addressshipping->phone_mobile = isset($address['phone']) ? $address['phone'] : $bill_phone;

                                $addressshipping->address1 = $address['street'];
                                $addressshipping->postcode = $address['zip'];
                                $addressshipping->city = $address['city'];
                                $addressshipping->country = isset($address['country']) ? $address['country'] : $country;
                                $addressshipping->alias = 'Bimport-' . date('Y-m-d');


                                $_country = (isset($address['country']) AND $address['country'] != '') ? $address['country'] : $country;
                                $addressshipping->id_country = Country::getByIso($_country);
                                $addressshipping->save();
                                $shipping_address_id = $addressshipping->id;
                            } else {
                                $shipping_address_id = $matched_address_id;
                            }
                        }

                        $this->context->cart->id_address_invoice = (int)$billing_address_id;
                        $this->context->cart->id_address_delivery = (int)$shipping_address_id;

                        // Connect selected shipping method to delivery address
                        if (is_array($cart_delivery_option)) {
                            $cart_delivery_option = current($cart_delivery_option);
                        }
                        $this->actionSetShipping($cart_delivery_option);
                    }

                    $this->context->cart->update();
                    $this->context->cart->save();

                    $customer = new Customer($this->context->cart->id_customer);
                    $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
                    $extra = array('transaction_id' => $data['number']);

                    $status_key = 'B' . strtoupper($this->method) . '_ORDER_STATUS';
                    if ($this->method == 'checkout') {
                        $status_key = 'BILLMATE_CHECKOUT_ORDER_STATUS';
                    }

                    $status = Configuration::get($status_key);
                    $status = ($data['status'] == 'Pending') ? Configuration::get('BILLMATE_PAYMENT_PENDING') : $status;
                    $total = $paymentInfo['Cart']['Total']['withtax'] / 100;

                    $this->module->validateOrder((int)$this->context->cart->id, $status,
                        $total, $displayName, null, $extra, null, false, $customer->secure_key);
                    $values = array();

                    $values['PaymentData'] = array(
                        'number' => $data['number'],
                        'orderid' => (Configuration::get('BILLMATE_SEND_REFERENCE') == 'reference') ? $this->module->currentOrderReference : $this->module->currentOrder
                    );
                    $this->billmate->updatePayment($values);
                    if ($this->module->authorization_method == 'sale' && ($this->method == 'cardpay')) {

                        $values['PaymentData'] = array(
                            'number' => $data['number']
                        );
                        $this->billmate->activatePayment($values);
                    }
                    unlink($lockfile);

                    if (isset($this->context->cookie->billmatepno)) {
                        unset($this->context->cookie->billmatepno);
                    }

                    $realModuleId = Module::getModuleIdByName($this->module->name);
                    $realOrder = new Order((int)$this->module->currentOrder);
                    $realCustomerId = $realOrder->id_customer;
                    $realCustomer = new Customer((int)$realCustomerId);

                    Tools::redirect('index.php?controller=order-confirmation' .
                        '&id_cart=' . (int)$this->context->cart->id .
                        '&id_module=' . (int)$realModuleId .
                        '&key=' . $realCustomer->secure_key .
                        '&token=3'
                    );
                    die;
                } else {
                    $order = $data['orderid'];
                    $order = explode('-', $order);
                    Logger::addLog($data['message'], 1, $data['code'], 'Cart', $order[0]);
                }
            }
            catch (Exception $e){
                PrestaShopLogger::addLog("order creation error: " . $e->getMessage(), 4);
            }
        }

        public function fetchCheckout()
        {
            $billmate = $this->getBillmate();
            if ($hash = Common::getCartCheckoutHash()) {
                $result = $billmate->getCheckout(array('PaymentData' => array('hash' => $hash)));
                if(!isset($result['code'])){
                    return $result;
                }
            }
            return array();
        }

        public function getCheckout()
        {
            $billmate = $this->getBillmate();
            if ($hash = Common::getCartCheckoutHash()) {
                $result = $billmate->getCheckout(array('PaymentData' => array('hash' => $hash)));

                if (!isset($result['code'])) {
                    if (    isset($result['PaymentData']['order']) AND
                            isset($result['PaymentData']['order']['status']) AND
                            (   strtolower($result['PaymentData']['order']['status']) != 'created' OR
                                strtolower($result['PaymentData']['order']['status']) != 'paid'
                            )
                    ) {
                        /** Checkout order paid, init new checkout order */
                        $result = $this->initCheckout();
                        if (!isset($result['code'])) {
                            return $result['url'];
                        }
                    } else {
                        /** Checkout order not paid, update checkout order */
                        $updateResult = $this->updateCheckout($result);
                        if (!isset($updateResult['code'])) {

                            /** Store returned hash */
                            $hash = $this->getHashFromUrl($updateResult['url']);
                            Common::setCartCheckoutHash($hash);

                            $result = $billmate->getCheckout(array('PaymentData' => array('hash' => $hash)));
                            return $result['PaymentData']['url'];
                        }
                    }
                }

            } else {
                $result = $this->initCheckout();
                if(!isset($result['code'])){
                    return $result['url'];
                }
            }
        }

        public function getBillmate()
        {
            $eid    = Configuration::get('BILLMATE_ID');
            $secret = Configuration::get('BILLMATE_SECRET');
            $testMode = Configuration::get('BILLMATE_CHECKOUT_TESTMODE');
            return Common::getBillmate($eid,$secret,$testMode);
        }

        public function actionSetShipping($delivery_option)
        {
            $result = array();
            $validated = false;
            try {
                if (!is_array($delivery_option)) {
                    $delivery_option = array(
                        $this->context->cart->id_address_delivery => $delivery_option
                    );
                }

                $validateOptionResult = $this->validateDeliveryOption($delivery_option);

                if ($validateOptionResult) {
                    $validated = true;
                    if(version_compare(_PS_VERSION_,'1.7','>=')
                        &&
                        !(version_compare(_PS_VERSION_,'1.7.5','>='))
                    ) {
                        $deliveryOption =  $delivery_option;
                        $realOption = array();
                        foreach ($deliveryOption as $key => $value){
                            $realOption[$key] = Cart::desintifier($value);
                        }
                        $this->context->cart->setDeliveryOption($realOption);
                    }
                    else {
                        $this->context->cart->setDeliveryOption($delivery_option);
                    }
                }

                $updated = true;
                $cartUpdateResult = $this->context->cart->update();
                if (!$cartUpdateResult) {
                    $updated = false;
                    $this->context->smarty->assign(array(
                        'vouchererrors' => Tools::displayError('Could not save carrier selection'),
                    ));
                }
                $cartSaveResult = $this->context->cart->save();

                // Carrier has changed, so we check if the cart rules still apply
                CartRule::autoRemoveFromCart($this->context);
                CartRule::autoAddToCart($this->context);
                $result['success'] = true;
            } catch(Exception $e){
                $result['success'] = false;
                $result['message'] = $e->getMessage();
                $result['trace'] = $e;
            }
            return $result;
        }

        protected function validateDeliveryOption($delivery_option)
        {
            if (!is_array($delivery_option)) {
                return false;
            }
            foreach ($delivery_option as $option) {
                if (!preg_match('/(\d+,)?\d+/', $option)) {
                    return false;
                }
            }
            return true;
        }

    }
