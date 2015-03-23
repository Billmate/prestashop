<?php

	/*
	 * Created by PhpStorm.
	 * User: jesper
	 * Date: 15-03-18
	 * Time: 18:17
	 * @author Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 */
	class BillmategatewayCancelModuleFrontController extends ModuleFrontController {

		public $ssl = true;
		public $ajax = true;

		public function postProcess()
		{
			$this->context = Context::getContext();

			$order_url = $this->context->link->getPageLink('order.php', true);
			Tools::redirectLink($order_url);
		}
	}