<?php
	/**
	 * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
	 *
	 * @author    Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 * @license   OpenSource
	 */

	/*
	 * The controller for Callbacks
	 */

	class BillmategatewayCallbackModuleFrontController extends ModuleFrontController {

		public $billmate;
		protected $method;
		protected $cart_id;
		public $module;
		protected $coremodule;


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

		public function postProcess()
		{
			$this->method = Tools::getValue('method');
			$eid          = Configuration::get('BILLMATE_ID');
			$secret       = Configuration::get('BILLMATE_SECRET');
			$ssl          = true;
			$debug        = false;
			require_once(_PS_MODULE_DIR_.'billmategateway/methods/'.Tools::ucfirst($this->method).'.php');

			$class        = "BillmateMethod".Tools::ucfirst($this->method);
			$this->module = new $class;
			$this->coremodule = new BillmateGateway();
			$testmode = $this->module->testMode;

			$this->billmate = Common::getBillmate($eid, $secret, $testmode, $ssl, $debug);

            $input = Tools::file_get_contents('php://input');
            $post = is_array($_GET) && isset($_GET['data']) ? $_GET : Tools::file_get_contents('php://input');

			if(is_array($post))
			{
				foreach($post as $key => $value)
					$post[$key] =  preg_replace_callback(
				"@\\\(x)?([0-9a-fA-F]{2})@",
				function($m){
					return chr($m[1]?hexdec($m[2]):octdec($m[2]));
				},
				$value
			);
			}
			$data = $this->billmate->verify_hash($post);

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
				$paymentInfo = $this->billmate->getPaymentinfo(array('number' => $data['number']));
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
				$order        = $data['orderid'];
				$order        = explode('-', $order);
				$this->cart_id = $order[0];
				$this->context->cart = new Cart($this->cart_id);
				if ($this->context->cart->orderExists() || $processing){
					error_log('order_exists');

					if ($processing)
						$order_id = $this->checkOrder($this->cart_id);
					else
						$order_id = Order::getOrderByCartId($this->cart_id);

					$orderObject = new Order($order_id);

					if($orderObject->current_state == Configuration::get('BILLMATE_PAYMENT_PENDING') && $data['status'] != 'Pending'){
						$orderHistory = new OrderHistory();
						$orderHistory->id_order = (int) $orderObject->id;

						$status              = Configuration::get('B'.strtoupper($this->method).'_ORDER_STATUS');
						$status = ($data['status'] == 'Cancelled') ? Configuration::get('PS_OS_CANCELED') : $status;
						$orderHistory->changeIdOrderState($status, $order_id, true);
						$orderHistory->add();
					}
					die('OK');
				}

				if($data['status'] == 'Cancelled')
					die('OK');
				file_put_contents($lockfile, 1);




				$customer            = new Customer($this->context->cart->id_customer);
				$total               = $this->context->cart->getOrderTotal(true, Cart::BOTH);
                $extra               = array('transaction_id' => $data['number']);

                $total = $paymentInfo['Cart']['Total']['withtax'] / 100;

                $status_key = 'B'.strtoupper($this->method).'_ORDER_STATUS';
                if ($this->method == 'checkout') {
                    $status_key = 'BILLMATE_CHECKOUT_ORDER_STATUS';
                }
                $status = Configuration::get($status_key);
                $status = ($data['status'] == 'Pending') ? Configuration::get('BILLMATE_PAYMENT_PENDING') : $status;

                $this->module->validateOrder((int)$this->context->cart->id,
                    $status,
                    $total,
                    $displayName,
                    null,
                    $extra,
                    null,
                    false,
                    $customer->secure_key
                );
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
				exit('finalize');
			}
			else
			{
				$order        = $data['orderid'];
				$order        = explode('-', $order);
				Logger::addLog($data['message'], 1, $data['code'], 'Cart', $order[0]);
			}
		}

	}