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

			$testmode = $this->module->testMode;

			$this->billmate = Common::getBillmate($eid, $secret, $testmode, $ssl, $debug);

			$data = $this->billmate->verify_hash(Tools::file_get_contents('php://input'));
			file_put_contents(_PS_CACHE_DIR_.'debugcall',print_r($data,true));
			if (!isset($data['code']) && !isset($data['error']))
			{
				$lockfile   = _PS_CACHE_DIR_.Tools::getValue('orderid');
				$processing = file_exists($lockfile);
				if ($this->context->cart->orderExists() || $processing)
					die('OK');

				file_put_contents($lockfile, 1);
				$order        = $data['orderid'];
				$order        = explode('-', $order);
				$this->cart_id = $order[0];

				$this->context->cart = new Cart($this->cart_id);
				$customer            = new Customer($this->context->cart->id_customer);
				$total               = $this->context->cart->getOrderTotal(true, Cart::BOTH);
				$extra               = array('transaction_id' => $data['number']);
				$status              = ($this->method == 'cardpay') ? Configuration::get('BCARDPAY_ORDER_STATUS') : Configuration::get('BBANKPAY_ORDER_STATUS');
				$this->module->validateOrder((int)$this->context->cart->id, $status, $total,
					$this->module->displayName, null, $extra, null, false, $customer->secure_key);
				$values = array();
				if ($this->module->authorization_method != 'sale' && ($this->method == 'cardpay' || $this->method == 'bankpay'))
				{
					$values['PaymentData'] = array(
						'number'  => $data['number'],
						'orderid' => (Configuration::get('BILLMATE_SEND_REFERENCE') == 1) ? $this->module->currentOrderReference : $this->module->currentOrder
					);
					$this->billmate->updatePayment($values);
				}
				if ($this->module->authorization_method == 'sale' && ($this->method == 'cardpay' || $this->method == 'bankpay'))
				{

					$values['PaymentData'] = array(
						'number' => $data['number']
					);
					$this->billmate->activatePayment($values);
				}
				unlink($lockfile);
				exit('finalize');
			}
			{
				$order        = $data['orderid'];
				$order        = explode('-', $order);
				Logger::addLog($data['message'],1,$data['code'],'Cart',$order[0]);
			}
		}

	}