<?php
	/**
	 * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
	 *
	 * @author    Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 * @license   OpenSource
	 */

	/*
	 * The controller for accept payment
	 */

	class BillmategatewayAcceptModuleFrontController extends ModuleFrontController {

		public $module;
		protected $method;
		protected $billmate;
		protected $cart_id;
		protected $coremodule;

		/**
		 * A recursive method which delays order-confirmation until order is processed
		 *
		 * @param $cart_id Cart Id
		 *
		 * @return integer OrderId
		 */

		private function checkOrder($cart_id)
		{
			$order = Order::getOrderByCartId($cart_id);
			if (!$order)
			{
				sleep(1);
				$this->checkOrder($cart_id);
			}
			else
				return $order;

		}

		public function getmoduleId($method)
		{
			$id2name = array();
			$sql = 'SELECT `id_module`, `name` FROM `'._DB_PREFIX_.'module`';
			if ($results = Db::getInstance()->executeS($sql)) {
				foreach ($results as $row) {
					$id2name[$row['name']] = $row['id_module'];
				}
			}

			return $id2name[$method];
		}

		public function postProcess()
		{
			$this->method = Tools::getValue('method');
			$eid          = Configuration::get('BILLMATE_ID');
			$secret       = Configuration::get('BILLMATE_SECRET');
			$ssl          = true;
			$debug        = false;

            $class_file = _PS_MODULE_DIR_.'billmategateway/methods/'.Tools::ucfirst($this->method).'.php';
			require_once($class_file);

			$class        = "BillmateMethod".Tools::ucfirst($this->method);
			$this->module = new $class;
			$this->coremodule = new BillmateGateway();

			$testmode = $this->module->testMode;

			$this->billmate      = Common::getBillmate($eid, $secret, $testmode, $ssl, $debug);
			$_POST               = !empty($_POST) ? $_POST : $_GET;
            $data               = $this->billmate->verify_hash($_POST);


			$order               = $data['orderid'];
			$order               = explode('-', $order);
			$this->cart_id        = $order[0];
			$this->context->cart = new Cart($this->cart_id);
			$customer            = new Customer($this->context->cart->id_customer);
			$logfile   = _PS_CACHE_DIR_.'Billmate.log';

            $paymentInfo = array();
            if (!isset($data['code']) && !isset($data['error'])) {
                $paymentInfo = $this->billmate->getPaymentinfo(array('number' => $data['number']));
            }


            $displayName = $this->module->displayName;
            if ($this->method == 'checkout') {
                /** When checkout, check for selected payment method found in $paymentInfo.PaymentData.method_name */
                if (isset($paymentInfo['PaymentData']['method_name']) AND $paymentInfo['PaymentData']['method_name'] != '') {
                    $displayName = $displayName.' ('.$paymentInfo['PaymentData']['method_name'].')';
                }
            }


			if (!isset($data['code']) && !isset($data['error']))
			{
				if (!isset($paymentInfo['code']) AND $this->method != 'checkout') {
					switch($paymentInfo['PaymentData']['method']){
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

                $class_file = _PS_MODULE_DIR_.'billmategateway/methods/'.Tools::ucfirst($this->method).'.php';
                require_once($class_file);
				$class        = "BillmateMethod".Tools::ucfirst($this->method);
				$this->module = new $class;

				$lockfile   = _PS_CACHE_DIR_.$data['orderid'];
				$processing = file_exists($lockfile);
				if ($this->context->cart->orderExists() || $processing)
				{
					$order_id = 0;
					if ($processing)
						$order_id = $this->checkOrder($this->context->cart->id);
					else
						$order_id = Order::getOrderByCartId($this->context->cart->id);

					$orderObject = new Order($order_id);
					if($orderObject->current_state == Configuration::get('BILLMATE_PAYMENT_PENDING') && $data['status'] != 'Pending'){
						$orderHistory = new OrderHistory();

                        $status_key = 'B'.strtoupper($this->method).'_ORDER_STATUS';
                        if ($this->method == 'checkout') {
                            $status_key = 'BILLMATE_CHECKOUT_ORDER_STATUS';
                        }

						$status              = Configuration::get($status_key);

						$status = ($data['status'] == 'Cancelled') ? Configuration::get('PS_OS_CANCELED') : $status;
						$orderHistory->id_order = (int) $orderObject->id;
						$orderHistory->changeIdOrderState($status,(int) $orderObject->id, true);
						$orderHistory->add();
					}
					if(isset($this->context->cookie->billmatepno))
						unset($this->context->cookie->billmatepno);

                    if ('' != Common::getCartCheckoutHash()) {
                        $hash = Common::getCartCheckoutHash();
                        Common::unsetCartCheckoutHash();
						$url = $this->context->link->getModuleLink(
							'billmategateway',
							'thankyou',
							array('billmate_hash' => $hash));
						Tools::redirectLink($url);
						die;
					} else {


						Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation.php?key=' . $customer->secure_key .
							'&id_cart=' . (int)$this->context->cart->id . '&id_module=' . (int)$this->getmoduleId('billmate' . $this->method) .
							'&id_order=' . (int)$order_id);
						die;
					}
				} else {


                    if ($paymentInfo['PaymentData']['method'] == '1' OR '2' == $paymentInfo['PaymentData']['method']) {
                        /** Paid with invoice */
                        $this->paymentClass = 'invoice';
                        $class_file = _PS_MODULE_DIR_.'billmategateway/methods/'.Tools::ucfirst($this->paymentClass).'.php';
                        require_once($class_file);
                        $class        = "BillmateMethod".Tools::ucfirst($this->paymentClass);
                        $this->module = new $class;
                    }


                    $return = array();
                    $paymentInfo = $this->billmate->getPaymentinfo(array('number' => $data['number']));
                    $result = $paymentInfo;

                    if (!isset($result['code']) && (isset($result['PaymentData']['order']['number']) && is_numeric($result['PaymentData']['order']['number']) && $result['PaymentData']['order']['number'] > 0)) {

                    $status = ($this->method == 'invoice') ? Configuration::get('BINVOICE_ORDER_STATUS') : Configuration::get('BPARTPAY_ORDER_STATUS');
                    $status = ($this->method == 'invoiceservice') ? Configuration::get('BINVOICESERVICE_ORDER_STATUS') : $status;

                    if ($this->method == 'checkout') {
                        $status_key = 'BILLMATE_CHECKOUT_ORDER_STATUS';
                        $status = Configuration::get($status_key);
                    }
                    $status = ($result['PaymentData']['order']['status'] == 'Pending') ? Configuration::get('BILLMATE_PAYMENT_PENDING') : $status;

                    if(Validate::isLoadedObject($this->context->cart) && $this->context->cart->OrderExists() == false) {
                        $extra = array('transaction_id' => $result['PaymentData']['order']['number']);
                        $total = $this->context->cart->getOrderTotal();
                        $total = $result['Cart']['Total']['withtax'];
                        $total = $total/100;
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
                    $url = $this->context->link->getModuleLink(
                        'billmatecheckout',
                        'thankyou',
                        array('BillmateHash' => Common::getCartCheckoutHash())
                    );

                    $return['success'] = true;
                    $return['redirect'] = $url;
                    Common::unsetCartCheckoutHash();
                } else {
                    if (isset($result['code']) AND in_array($result['code'], array(2401, 2402, 2403, 2404, 2405))) {
                        if (is_array($result)) {
                            // die(Tools::jsonEncode($result));
                        }
                    }
                    //Logger::addLog($result['message'], 1, $result['code'], 'Cart', $this->context->cart->id);
                    $_message = (isset($result['message'])) ? $result['message'] : '';
                    $return = array('success' => false, 'content' => utf8_encode($_message));
                }
            }

				file_put_contents($lockfile, 1);

                $cart_delivery_option = $this->context->cart->getDeliveryOption();

                if (
                    $this->context->cart->id_customer == 0
                    || $this->context->cart->id_address_invoice  == 0
                    || $this->context->cart->id_address_delivery == 0
                ) {
                    $result     = $this->fetchCheckout();
                    $customer   = $result['Customer'];
                    $address    = $customer['Billing'];
                    $country    = isset($customer['Billing']['country']) ? $customer['Billing']['country'] : 'SE';
                    $bill_phone = isset($customer['Billing']['phone']) ? $customer['Billing']['phone'] : '';
                }

                /** Create customer when missing */
                if ($this->context->cart->id_customer == 0) {
                    $customerObject = new Customer();
                    $password = Tools::passwdGen(8);
                    $customerObject->firstname = !empty($address['firstname']) ? $address['firstname'] : '';
                    $customerObject->lastname  = !empty($address['lastname']) ? $address['lastname'] : '';
                    $customerObject->company   = isset($address['company']) ? $address['company'] : '';
                    $customerObject->passwd = $password;
                    $customerObject->id_default_group = (int) (Configuration::get('PS_CUSTOMER_GROUP', null, $this->context->cart->id_shop));

                    $customerObject->email = $address['email'];
                    $customerObject->active = true;
                    $customerObject->add();
                    $this->context->customer = $customerObject;
                    $this->context->cart->secure_key = $customerObject->secure_key;
                    $this->context->cart->id_customer = $customerObject->id;
                }

                /** Create billing/shipping address when missing */
                if ($this->context->cart->id_address_invoice  == 0 || $this->context->cart->id_address_delivery == 0) {

                    $_customer = new Customer($this->context->cart->id_customer);
                    $customer_addresses = $_customer->getAddresses($this->context->language->id);

                    if (count($customer_addresses) == 1) {
                        $customer_addresses[] = $customer_addresses;
                    }

                    $matched_address_id = false;
                    foreach ($customer_addresses as $customer_address) {
                        if (isset($customer_address['address1'])) {
                            $billing  = new Address($customer_address['id_address']);

                            $user_bill = $billing->firstname.' '.$billing->lastname.' '.$billing->company;
                            $company = isset($address['company']) ? $address['company'] : '';
                            $api_name = $address['firstname']. ' '. $address['lastname'].' '.$company;

                            if (Common::matchstr($user_bill,$api_name) && Common::matchstr($customer_address['address1'], $address['street']) &&
                                Common::matchstr($customer_address['postcode'], $address['zip']) &&
                                Common::matchstr($customer_address['city'], $address['city']) &&
                                Common::matchstr(Country::getIsoById($customer_address['id_country']), $address['country']))

                                $matched_address_id = $customer_address['id_address'];
                        } else {
                            foreach ($customer_address as $c_address) {
                                $billing  = new Address($c_address['id_address']);

                                $user_bill = $billing->firstname.' '.$billing->lastname.' '.$billing->company;
                                $company = isset($address['company']) ? $address['company'] : '';
                                $api_name = $address['firstname']. ' '. $address['lastname'].' '.$company;


                                if (
                                    Common::matchstr($user_bill,$api_name) &&  Common::matchstr($c_address['address1'], $address['street'])
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
                        $addressnew              = new Address();
                        $addressnew->id_customer = (int)$this->context->cart->id_customer;

                        $addressnew->firstname = !empty($address['firstname']) ? $address['firstname'] : $billing->firstname;
                        $addressnew->lastname  = !empty($address['lastname']) ? $address['lastname'] : $billing->lastname;
                        $addressnew->company   = isset($address['company']) ? $address['company'] : '';

                        $addressnew->phone        = $address['phone'];
                        $addressnew->phone_mobile = $address['phone'];

                        $addressnew->address1   = $address['street'];
                        $addressnew->postcode   = $address['zip'];
                        $addressnew->city       = $address['city'];
                        $addressnew->country    = $address['country'];
                        $addressnew->alias      = 'Bimport-'.date('Y-m-d');
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
                        file_put_contents($logfile, 'shippingAddress:'.print_r($address,true),FILE_APPEND);
                        file_put_contents($logfile, 'customerAddress:'.print_r($customer_addresses,true),FILE_APPEND);

                        $matched_address_id = false;
                        foreach ($customer_addresses as $customer_address) {
                            if (isset($customer_address['address1'])) {
                                $billing  = new Address($customer_address['id_address']);

                                $user_bill = $billing->firstname.' '.$billing->lastname.' '.$billing->company;
                                $company = isset($address['company']) ? $address['company'] : '';
                                $api_name = $address['firstname']. ' '. $address['lastname'].' '.$company;

                                if (Common::matchstr($user_bill,$api_name) && Common::matchstr($customer_address['address1'], $address['street']) &&
                                    Common::matchstr($customer_address['postcode'], $address['zip']) &&
                                    Common::matchstr($customer_address['city'], $address['city']) &&
                                    Common::matchstr(Country::getIsoById($customer_address['id_country']), isset($address['country']) ? $address['country'] : $country)) {
                                        $matched_address_id = $customer_address['id_address'];
                                    }
                            } else {
                                foreach ($customer_address as $c_address) {
                                    $billing  = new Address($c_address['id_address']);

                                    $user_bill = $billing->firstname.' '.$billing->lastname.' '.$billing->company;
                                    $company = isset($address['company']) ? $address['company'] : '';
                                    $api_name = $address['firstname']. ' '. $address['lastname'].' '.$company;


                                    if (Common::matchstr($user_bill,$api_name) &&  Common::matchstr($c_address['address1'], $address['street']) &&
                                        Common::matchstr($c_address['postcode'], $address['zip']) &&
                                        Common::matchstr($c_address['city'], $address['city']) &&
                                        Common::matchstr(Country::getIsoById($c_address['id_country']), isset($address['country']) ? $address['country'] : $country)
                                    ) {
                                        $matched_address_id = $c_address['id_address'];
                                    }
                                }
                            }

                        }
                        if(!$matched_address_id) {
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

                    $this->context->cart->id_address_invoice  = (int)$billing_address_id;
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
				$total  = $this->context->cart->getOrderTotal(true, Cart::BOTH);
				$extra  = array('transaction_id' => $data['number']);

                $status_key = 'B'.strtoupper($this->method).'_ORDER_STATUS';
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
					'number'  => $data['number'],
					'orderid' => (Configuration::get('BILLMATE_SEND_REFERENCE') == 'reference') ? $this->module->currentOrderReference : $this->module->currentOrder
				);
				$this->billmate->updatePayment($values);
                if ($this->module->authorization_method == 'sale' && (($this->method == 'cardpay') || ($this->method == 'checkout' && $paymentInfo['PaymentData']['method_name'] == 'Kortbetalning')))
				{

					$values['PaymentData'] = array(
						'number' => $data['number']
					);
					$this->billmate->activatePayment($values);
				}
				unlink($lockfile);
				if(isset($this->context->cookie->billmatepno))
					unset($this->context->cookie->billmatepno);

                if ('' != Common::getCartCheckoutHash()) {
                    $hash = Common::getCartCheckoutHash();
                    Common::unsetCartCheckoutHash();
					$url = $this->context->link->getModuleLink(
						'billmategateway',
						'thankyou',
						array('billmate_hash' => $hash));
					Tools::redirectLink($url);
					die;
				} else {


					Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation.php?key=' . $customer->secure_key .
						'&id_cart=' . (int)$this->context->cart->id . '&id_module=' . (int)$this->getmoduleId('billmate' . $this->method) .
						'&id_order=' . (int)$this->module->currentOrder);
					die;
				}


			}
			else
			{
				$order        = $data['orderid'];
				$order        = explode('-', $order);
				Logger::addLog($data['message'], 1, $data['code'], 'Cart', $order[0]);
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
