<?php

require_once BCARDPAY_BASE. '/Billmate.php';
require_once BCARDPAY_BASE .'/lib/billmateCart.php';

class BillmateCardpayCancelorderModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $ajax = true;

	public function postProcess()
	{
		$this->context = Context::getContext();

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