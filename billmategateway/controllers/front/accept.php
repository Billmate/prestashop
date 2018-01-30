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
						case '1024':
							$this->method = 'swish';
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
                        $this->method = 'invoice';
                        $class_file = _PS_MODULE_DIR_.'billmategateway/methods/'.Tools::ucfirst($this->method).'.php';
                        require_once($class_file);
                        $class        = "BillmateMethod".Tools::ucfirst($this->method);
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

				if ($this->module->authorization_method == 'sale' && $this->method == 'cardpay')
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

	}