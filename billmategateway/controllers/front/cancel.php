<?php
	/**
	 * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
	 *
	 * @author    Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 * @license   OpenSource
	 */

	/*
	 * The controller for canceled payments
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