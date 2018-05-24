<?php
	/**
	 * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
	 *
	 * @author    Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 * @license   OpenSource
	 */

	/*
	 * Class for BillmatePartpayment related stuff
	 */
require_once(_PS_MODULE_DIR_.'billmategateway/billmategateway.php');


	class BillmateMethodPartpay extends BillmateGateway {

		public function __construct()
		{
			parent::__construct();
			$this->name                 = 'billmatepartpay';
            $this->module = new BillmateGateway();
			$this->displayName          = $this->module->l('Billmate Part Pay','partpay');
			$this->testMode             = Configuration::get('BPARTPAY_MOD');
			$this->min_value            = Configuration::get('BPARTPAY_MIN_VALUE');
			$this->max_value            = Configuration::get('BPARTPAY_MAX_VALUE');
			$this->sort_order           = (Configuration::get('BPARTPAY_SORTORDER')) ? Configuration::get('BPARTPAY_SORTORDER') : 2;
			$this->limited_countries    = array('se');
			$this->allowed_currencies   = array('SEK');
			$this->authorization_method = false;
			$this->validation_controller = $this->context->link->getModuleLink('billmategateway', 'billmateapi', array('method' => 'partpay'),true);
			$this->icon                 = file_exists(_PS_MODULE_DIR_.'billmategateway/views/img/'.Tools::strtolower($this->context->language->iso_code).'/partpayment.png') ? 'billmategateway/views/img/'.Tools::strtolower($this->context->language->iso_code).'/partpayment.png' : 'billmategateway/views/img/en/partpayment.png';
		}



		/**
		 * Returns Payment info for appending in payment methods list
		 */
		public function getPaymentInfo($cart)
		{
			pClasses::checkPclasses($this->billmate_merchant_id,$this->billmate_secret,'se',Language::getIsoById($cart->id_lang),'SEK');
			if (!pClasses::hasPclasses(Language::getIsoById($cart->id_lang)) || Configuration::get('BPARTPAY_ENABLED') == 0)
				return false;

			if ($this->min_value > $this->context->cart->getOrderTotal())
				return false;
			if ($this->max_value < $this->context->cart->getOrderTotal())
				return false;
			if (!in_array(strtoupper($this->context->currency->iso_code), $this->allowed_currencies))
				return false;

			if (!in_array(Tools::strtolower($this->context->country->iso_code), $this->limited_countries))
				return false;


			$pclasses = $this->getPclasses($cart);
			if(empty($pclasses))
				return false;

			return array(
				'sort_order' => $this->sort_order,
				'name'         => $this->displayName,
				'type'         => $this->name,
				'controller'   => $this->validation_controller,
				'icon'         => $this->icon,
				'agreements'   => '<span>'.$this->module->l('My email is accurate and can be used for invoicing.','partpay').'<br/> <a id="terms-partpay" style="cursor:pointer!important">'.$this->module->l('I also confirm the terms for invoice payment','partpay').'</a>, <a id="billmate-privacy-policy" href="https://www.billmate.se/integritetspolicy/" target="_blank">'.$this->module->l('Privacy Policy', 'partpay').'</a> '.$this->module->l('and accept the liability.','partpay').'</span>',
				'pClasses'     => $pclasses,
				'monthly_cost' => $this->getMonthlyCost($cart)

			);
		}

		public function getSettings()
		{
			$settings       = array();
			$statuses       = OrderState::getOrderStates((int)$this->context->language->id);
			$currency       = Currency::getDefaultCurrency();
			$statuses_array = array();
			foreach ($statuses as $status)
				$statuses_array[$status['id_order_state']] = $status['name'];

			$settings['activated'] = array(
				'name'     => 'partpayActivated',
				'required' => true,
				'type'     => 'checkbox',
				'label'    => $this->module->l('Enabled','partpay'),
				'desc'     => $this->module->l('Enable Billmate Part payment','partpay'),
				'value'    => (Tools::safeOutput(Configuration::get('BPARTPAY_ENABLED'))) ? 1 : 0,

			);

			$settings['testmode'] = array(
				'name'     => 'partpayTestmode',
				'required' => true,
				'type'     => 'checkbox',
				'label'    => $this->module->l('Test Mode','partpay'),
				'desc'     => $this->module->l('Enable Test Mode','partpay'),
				'value'    => (Tools::safeOutput(Configuration::get('BPARTPAY_MOD'))) ? 1 : 0
			);

			$settings['order_status']  = array(
				'name'     => 'partpayBillmateOrderStatus',
				'required' => true,
				'type'     => 'select',
				'label'    => $this->module->l('Set Order Status','partpay'),
				'desc'     => $this->module->l(''),
				'value'    => (Tools::safeOutput(Configuration::get('BPARTPAY_ORDER_STATUS'))) ? Tools::safeOutput(Configuration::get('BPARTPAY_ORDER_STATUS')) : Tools::safeOutput(Configuration::get('PS_OS_PAYMENT')),
				'options'  => $statuses_array
			);
			$settings['minimum_value'] = array(
				'name'     => 'partpayBillmateMinimumValue',
				'required' => false,
				'value'    => (float)Configuration::get('BPARTPAY_MIN_VALUE'),
				'type'     => 'text',
				'label'    => $this->module->l('Minimum Value ','partpay').' ('.$currency->sign.')',
				'desc'     => '('.$this->module->l('Minimum Value ','partpay').': '.floor(pClasses::getLowestMinAmount()).')',
			);
			$settings['maximum_value'] = array(
				'name'     => 'partpayBillmateMaximumValue',
				'required' => false,
				'value'    => Configuration::get('BPARTPAY_MAX_VALUE') != 0 ? (float)Configuration::get('BPARTPAY_MAX_VALUE') : 99999,
				'type'     => 'text',
				'label'    => $this->module->l('Maximum Value ','partpay').' ('.$currency->sign.')',
				'desc'     => $this->module->l(''),
			);
			$settings['sort'] = array(
				'name'     => 'partpayBillmateSortOrder',
				'required' => false,
				'value'    => Configuration::get('BPARTPAY_SORTORDER'),
				'type'     => 'text',
				'label'    => $this->module->l('Sort Order','partpay'),
				'desc'     => $this->module->l(''),
			);
            if(Configuration::get('BILLMATE_ID'))
			    $pclasses = new pClasses(Configuration::get('BILLMATE_ID'));
			$settings['paymentplans'] = array(
				'name' => 'paymentplans',
				'label' => $this->module->l('Paymentplans','partpay'),
				'type' => 'table',
				'pclasses' => (Configuration::get('BILLMATE_ID')) ? $pclasses->getPClasses('') : false
			);


			return $settings;

		}

		public function getPclasses($cart)
		{
			$pclasses = new pClasses(Configuration::get('BILLMATE_ID'));

			return $pclasses->getPClasses('', $this->context->language->iso_code, true, $this->context->cart->getOrderTotal());
		}

		public function getMonthlyCost($cart)
		{
			$pclasses = new pClasses(Configuration::get('BILLMATE_ID'));

			return $pclasses->getCheapestPClass($this->context->cart->getOrderTotal(), BillmateFlags::CHECKOUT_PAGE, $this->context->language->iso_code);
		}

		public function getCheapestPlan($cost)
		{
			$pclasses = new pClasses(Configuration::get('BILLMATE_ID'));

			return $pclasses->getCheapestPClass($cost, BillmateFlags::CHECKOUT_PAGE, $this->context->language->iso_code);
		}
	}