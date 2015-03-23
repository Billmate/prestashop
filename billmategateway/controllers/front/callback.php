<?php
/**
 * Created by PhpStorm.
 * User: jesper
 * Date: 15-03-17
 * Time: 15:31
 */

class BillmategatewayCallbackModuleFrontController extends ModuleFrontController {

	public $billmate;
	protected $method;
	protected $cartId;
	public $module;

	public function postProcess()
	{
		$this->method = Tools::getValue( 'method' );
		$eid          = Configuration::get( 'BILLMATE_ID' );
		$secret       = Configuration::get( 'BILLMATE_SECRET' );
		$ssl          = true;
		$debug        = false;
		require_once( _PS_MODULE_DIR_.'billmategateway/methods/'.'Billmate'.ucfirst($this->method).'.php');

		$class        = 'Billmate' . ucfirst( $this->method );
		$this->module = new $class;

		$testmode = $this->module->testMode;

		$this->billmate = Common::getBillmate( $eid, $secret, $testmode, $ssl, $debug );

		$data = $this->billmate->verify_hash(Tools::jsonDecode(Tools::file_get_contents('php://input')));

		if(!isset($data['code']) && !isset($data['error']))
		{
			$lockfile = _PS_CACHE_DIR_.$_POST['order_id'];
			$processing = file_exists($lockfile);
			if ($this->context->cart->orderExists() || $processing)
				die('OK');

			file_put_contents($lockfile, 1);
			$order        = $data['orderid'];
			$order        = explode( '-', $order );
			$this->cartId = $order[0];



			$this->context->cart = new Cart( $this->cartId );
			$customer            = new Customer( $this->context->cart->id_customer );
			$total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
			$extra = array('transaction_id' => $data['number']);
			$status = ($this->method == 'cardpay') ? Configuration::get('BCARDPAY_ORDER_STATUS') : Configuration::get('BBANKPAY_ORDER_STATUS');
			$this->module->validateOrder((int)$this->context->cart->id,$status, $total, $this->module->displayName, null, $extra, null, false, $customer->secure_key);

			if($this->module->authorization_method != 'sale' && ($this->method == 'cardpay' || $this->method == 'bankpay'))
			{
				$values['PaymentData'] = array(
					'number'  => $data['number'],
					'orderid' => ( Configuration::get( 'BILLMATE_SEND_REFERENCE' ) == 1 ) ? $this->module->currentOrderReference : $this->module->currentOrder
				);
				$this->billmate->updatePayment($values);
			}
			if($this->module->authorization_method == 'sale' && ($this->method == 'cardpay' || $this->method == 'bankpay')){

				$values['PaymentData'] = array(
					'number' => $data['number']
				);
				$this->billmate->activatePayment($values);
			}
			unlink($lockfile);
			exit('finalize');
		}
	}

}