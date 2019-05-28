<?php
	/**
	 * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
	 *
	 * @author    Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 * @license   OpenSource
	 */

	/*
	 * Class for BillmateBankpay related stuff
	 */
require_once(_PS_MODULE_DIR_.'billmategateway/billmategateway.php');


	class BillmateMethodBankpay extends BillmateGateway {

		public function __construct()
		{
			parent::__construct();
			$this->name                 = 'billmatebankpay';
            $this->module = new BillmateGateway();
			$this->testMode             = Configuration::get('BBANKPAY_MOD');
			$this->displayName          = $this->module->l('Billmate Bankpay','bankpay');
			$this->limited_countries    = array('BE','DK','EE','FI','FR','IE','IT','LV','LT','MT','NL','NO','PL','PT','ES','SE','CZ','DE','AT');
			$this->allowed_currencies   = array('SEK','EUR','PLN','DKK');
			$this->min_value            = Configuration::get('BBANKPAY_MIN_VALUE');
			$this->max_value            = Configuration::get('BBANKPAY_MAX_VALUE');
			$this->authorization_method = Configuration::get('BBANKPAY_AUTHORIZATION_METHOD');
			$this->sort_order           = (Configuration::get('BBANKPAY_SORTORDER')) ? Configuration::get('BBANKPAY_SORTORDER') : 4;
			$this->validation_controller = $this->context->link->getModuleLink('billmategateway', 'billmateapi', array('method' => 'bankpay'),true);
			$this->icon                 = file_exists(_PS_MODULE_DIR_.'billmategateway/views/img/'.Tools::strtolower($this->context->language->iso_code).'/bank.png') ? 'billmategateway/views/img/'.Tools::strtolower($this->context->language->iso_code).'/bank.png' : 'billmategateway/views/img/en/bank.png';
		}



		/**
		 * Returns Payment info for appending in payment methods list
		 */
		public function getPaymentInfo($cart)
		{
			if (Configuration::get('BBANKPAY_ENABLED') == 0)
				return false;
			if ($this->min_value > $this->context->cart->getOrderTotal())
				return false;
			if ($this->max_value < $this->context->cart->getOrderTotal())
				return false;
			if (!in_array(strtoupper($this->context->currency->iso_code), $this->allowed_currencies))
				return false;
			if (!in_array(Tools::strtoupper($this->context->country->iso_code), $this->limited_countries))
				return false;

			return array(
				'sort_order' => $this->sort_order,
				'name'       => $this->displayName,
				'type'       => $this->name,
				'method'    => 'bankpay',
				'controller' => $this->validation_controller,
				'icon'       => $this->icon
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
				'name'     => 'bankpayActivated',
				'required' => true,
				'type'     => 'checkbox',
				'label'    => $this->module->l('Enabled','bankpay'),
				'desc'     => $this->module->l('Enable Billmate Bank','bankpay'),
				'value'    => (Tools::safeOutput(Configuration::get('BBANKPAY_ENABLED'))) ? 1 : 0,

			);

			$settings['testmode']      = array(
				'name'     => 'bankpayTestmode',
				'required' => true,
				'type'     => 'checkbox',
				'label'    => $this->module->l('Test Mode','bankpay'),
				'desc'     => $this->module->l('Enable Test Mode','bankpay'),
				'value'    => (Tools::safeOutput(Configuration::get('BBANKPAY_MOD'))) ? 1 : 0
			);

			$settings['order_status']  = array(
				'name'     => 'bankpayBillmateOrderStatus',
				'required' => true,
				'type'     => 'select',
				'label'    => $this->module->l('Set Order Status','bankpay'),
				'desc'     => $this->module->l(''),
				'value'    => (Tools::safeOutput(Configuration::get('BBANKPAY_ORDER_STATUS'))) ? Tools::safeOutput(Configuration::get('BBANKPAY_ORDER_STATUS')) : Tools::safeOutput(Configuration::get('PS_OS_PAYMENT')),
				'options'  => $statuses_array
			);
			$settings['minimum_value'] = array(
				'name'     => 'bankpayBillmateMinimumValue',
				'required' => false,
				'value'    => (float)Configuration::get('BBANKPAY_MIN_VALUE'),
				'type'     => 'text',
				'label'    => $this->module->l('Minimum Value ','bankpay').' ('.$currency->sign.')',
				'desc'     => $this->module->l(''),
			);
			$settings['maximum_value'] = array(
				'name'     => 'bankpayBillmateMaximumValue',
				'required' => false,
				'value'    => Configuration::get('BBANKPAY_MAX_VALUE') != 0 ? (float)Configuration::get('BBANKPAY_MAX_VALUE') : 99999,
				'type'     => 'text',
				'label'    => $this->module->l('Maximum Value ','bankpay').' ('.$currency->sign.')',
				'desc'     => $this->module->l(''),
			);
			$settings['sort'] = array(
				'name'     => 'bankpayBillmateSortOrder',
				'required' => false,
				'value'    => Configuration::get('BBANKPAY_SORTORDER'),
				'type'     => 'text',
				'label'    => $this->module->l('Sort Order','bankpay'),
				'desc'     => $this->module->l(''),
			);

			return $settings;

		}

	}