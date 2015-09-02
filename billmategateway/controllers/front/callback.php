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
			require_once(_PS_MODULE_DIR_.'billmategateway/methods/Billmate'.Tools::ucfirst($this->method).'.php');

			$class        = 'Billmate'.Tools::ucfirst($this->method);
			$this->module = new $class;
			$this->coremodule = new BillmateGateway();
			$testmode = $this->module->testMode;

			$this->billmate = Common::getBillmate($eid, $secret, $testmode, $ssl, $debug);

			$post = empty(Tools::file_get_contents('php://input')) ? $_GET : Tools::file_get_contents('php://input');

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

			if (!isset($data['code']) && !isset($data['error']))
			{
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
						$status              = ($this->method == 'cardpay') ? Configuration::get('BCARDPAY_ORDER_STATUS') : Configuration::get('BBANKPAY_ORDER_STATUS');

						$orderHistory->changeIdOrderState($status, $order_id, true);
					}
					die('OK');
				}


				file_put_contents($lockfile, 1);




				$customer            = new Customer($this->context->cart->id_customer);
				$total               = $this->context->cart->getOrderTotal(true, Cart::BOTH);
				$extra               = array('transaction_id' => $data['number']);
				$status              = ($this->method == 'cardpay') ? Configuration::get('BCARDPAY_ORDER_STATUS') : Configuration::get('BBANKPAY_ORDER_STATUS');
				$status = ($data['status'] == 'Pending') ? Configuration::get('BILLMATE_PAYMENT_PENDING') : $status;
				$this->coremodule->validateOrder((int)$this->context->cart->id, $status, $total,
					$this->module->displayName, null, $extra, null, false, $customer->secure_key);
				$values = array();
				if ($this->module->authorization_method != 'sale' && $this->method == 'cardpay' || $this->method == 'bankpay')
				{
					$values['PaymentData'] = array(
						'number'  => $data['number'],
						'orderid' => (Configuration::get('BILLMATE_SEND_REFERENCE') == 'reference') ? $this->coremodule->currentOrderReference : $this->coremodule->currentOrder
					);
					$this->billmate->updatePayment($values);
				}
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