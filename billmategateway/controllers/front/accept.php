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
			require_once(_PS_MODULE_DIR_.'billmategateway/methods/'.Tools::ucfirst($this->method).'.php');

			$class        = "BillmateMethod".Tools::ucfirst($this->method);
			$this->module = new $class;
			$this->coremodule = new BillmateGateway();

			$testmode = $this->module->testMode;

			$this->billmate      = Common::getBillmate($eid, $secret, $testmode, $ssl, $debug);
			$_POST               = !empty($_POST) ? $_POST : $_GET;
			$data                = $this->billmate->verify_hash($_POST);
			$order               = $data['orderid'];
			$order               = explode('-', $order);
			$this->cart_id        = $order[0];
			$this->context->cart = new Cart($this->cart_id);
			$customer            = new Customer($this->context->cart->id_customer);
			$logfile   = _PS_CACHE_DIR_.'Billmate.log';

			if (!isset($data['code']) && !isset($data['error']))
			{
				$paymentInfo = $this->billmate->getPaymentinfo(array('number' => $data['number']));

				if(!isset($paymentInfo['code'])){
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
						$status              = Configuration::get('B'.strtoupper($this->method).'_ORDER_STATUS');
						$status = ($data['status'] == 'Cancelled') ? Configuration::get('PS_OS_CANCELED') : $status;
						$orderHistory->id_order = (int) $orderObject->id;
						$orderHistory->changeIdOrderState($status,(int) $orderObject->id, true);
						$orderHistory->add();
					}
					if(isset($this->context->cookie->billmatepno))
						unset($this->context->cookie->billmatepno);

					if(isset($this->context->cookie->BillmateHash)){
						$hash = $this->context->cookie->BillmateHash;
						unset($this->context->cookie->BillmateHash);
						$url = $this->context->link->getModuleLink(
							'billmatecheckout',
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
				}

				file_put_contents($lockfile, 1);

				$total  = $this->context->cart->getOrderTotal(true, Cart::BOTH);
				$extra  = array('transaction_id' => $data['number']);
				$status = Configuration::get('B'.strtoupper($this->method).'_ORDER_STATUS');;
				$status = ($data['status'] == 'Pending') ? Configuration::get('BILLMATE_PAYMENT_PENDING') : $status;
				$total = $paymentInfo['Cart']['Total']['withtax'] / 100;
				$this->module->validateOrder((int)$this->context->cart->id, $status,
					$total, $this->module->displayName, null, $extra, null, false, $customer->secure_key);
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
				if(isset($this->context->cookie->BillmateHash)){
					$hash = $this->context->cookie->BillmateHash;
					unset($this->context->cookie->BillmateHash);
					$url = $this->context->link->getModuleLink(
						'billmatecheckout',
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