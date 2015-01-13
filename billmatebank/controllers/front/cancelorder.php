<?php

require_once BBANK_BASE. '/Billmate.php';
require_once BBANK_BASE .'/lib/billmateCart.php';

class BillmateBankCancelorderModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $ajax = true;

	public function postProcess()
	{
		$this->context = Context::getContext();
		$ids = explode("-",$_POST['order_id']);
		if( sizeof($ids) < 2 ) return false;
		$_POST['order_id'] = $ids[0];
		$_POST['cart_id'] = $ids[1];
		$order = new Order($_POST['order_id']);

		$new_history = new OrderHistory();
		$new_history->id_order = (int)$order->id;
		$new_history->changeIdOrderState((int)Configuration::get('PS_OS_CANCELED'), $order->id, true);
		$new_history->addWithemail(true);
		$orderUrl = $this->context->link->getPageLink('order.php', true);
		Tools::redirectLink($orderUrl);
	}
	
	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		$this->display_column_left = false;
	}
}