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

			$eid          = Configuration::get('BILLMATE_ID');
			$secret       = Configuration::get('BILLMATE_SECRET');
			$ssl          = true;
			$debug        = false;


			$this->billmate      = Common::getBillmate($eid, $secret, false, $ssl, $debug);
			$_POST               = !empty($_POST) ? $_POST : $_GET;
			$data                = $this->billmate->verify_hash($_POST);
			$this->coremodule = new BillmateGateway();
			if(isset($data['code'])){
				$this->errors[] = $this->coremodule->l('Unfortunately your card payment was not processed with the provided card details. Please try again or choose another payment method.');
			}
			if(isset($data['status'])){
				switch(strtolower($data['status'])){
					case 'failed':
						$this->errors[] = $this->coremodule->l('Unfortunately your card payment was not processed with the provided card details. Please try again or choose another payment method.');
						break;
					case 'cancelled':
						$this->errors[] = $this->coremodule->l('The card payment has been canceled before it was processed. Please try again or choose a different payment method.');
						break;
				}
			}
			$order_url = $this->context->link->getPageLink('order.php', true);
			if(isset($_GET['type']) && $_GET['type'] == 'checkout')
				$order_url = $this->context->link->getModuleLink('billmatecheckout', 'billmatecheckout', array(), true);

			if (version_compare(_PS_VERSION_,'1.7','>='))
				$this->redirectWithNotifications($order_url);
			else
				Tools::redirectLink($order_url);
		}
	}