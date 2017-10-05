<?php
/**
 * Created by PhpStorm.
 * User: Boxedsolutions
 * Date: 2017-08-01
 * Time: 08:51
 */
class BillmatecheckoutThankyouModuleFrontController extends ModuleFrontController{

	public $display_column_left = true;
	public $display_column_right = false;
	public $ssl = true;
	public $thankyou_content = null;

	public function initContent()
	{
		parent::initContent();


		$billmate_url = $this->getThankyou();

		$order_id = $this->thankyou_content['PaymentData']['orderid'];
		$order_id = explode('-',$order_id);
		$result = $this->verifyOrder($order_id[0]);

		$this->context->smarty->assign(array(
			'billmate_thankyou' => $billmate_url,
			'HOOK_HEADER' => Hook::exec('displayHeader'),
			'order_conf' => $this->displayOrderConfirmation((int) ($result['id_order'])),
		));
		if(version_compare(_PS_VERSION_,'1.7','>=')){
			$this->setTemplate('module:billmatecheckout/views/templates/front/billmate_thankyou17.tpl');

		} else {
			$this->setTemplate('billmate_thankyou.tpl');
		}
	}

	public function verifyOrder($id_order)
	{
		$sql = 'SELECT id_order FROM '._DB_PREFIX_.'orders WHERE id_cart='.$id_order;
		$result = Db::getInstance()->getRow($sql);
		if(!isset($result['id_order'])){
			sleep(2);
			$this->verifyOrder($id_order);
		}

		return $result;
	}

	public function getThankyou()
	{
		$billmate = $this->getBillmate();
		$result = $billmate->getCheckout(array('PaymentData' => array('hash' => Tools::getValue('billmate_hash', 0))));
		$this->thankyou_content = $result;
		return $result['PaymentData']['url'];
	}

	public function getBillmate()
	{
		$eid    = Configuration::get('BILLMATE_ID');
		$secret = Configuration::get('BILLMATE_SECRET');
		$testMode = Configuration::get('BILLMATE_CHECKOUT_TESTMODE');
		return Common::getBillmate($eid,$secret,$testMode);
	}


	/**
	 * Make sure order is correct
	 * @param $id_order integer
	 * @return bool
	 */

	public function displayOrderConfirmation($id_order)
	{
		if (Validate::isUnsignedId($id_order)) {
			$params = array();
			$order = new Order($id_order);
			$currency = new Currency($order->id_currency);

			if (Validate::isLoadedObject($order)) {
				$params['total_to_pay'] = $order->getOrdersTotalPaid();
				$params['currency'] = $currency->sign;
				$params['objOrder'] = $order;
				$params['currencyObj'] = $currency;

				return Hook::exec('displayOrderConfirmation', $params);
			}
		}

		return false;
	}
}