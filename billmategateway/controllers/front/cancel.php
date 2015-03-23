<?php

	/**
	 * Created by PhpStorm.
	 * User: jesper
	 * Date: 15-03-18
	 * Time: 18:17
	 */
	class BillmategatewayCancelModuleFrontController extends ModuleFrontController {

		public $ssl = true;
		public $ajax = true;

		public function postProcess()
		{
			$this->context = Context::getContext();

			$orderUrl = $this->context->link->getPageLink('order.php', true);
			Tools::redirectLink($orderUrl);
		}
	}