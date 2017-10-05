<?php
	/**
	 * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
	 *
	 * @author    Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 * @license   OpenSource
	 */

	/*
	 * Class for BillmateCardpay related stuff
	 */

require_once(_PS_MODULE_DIR_.'billmategateway/billmategateway.php');

	class BillmateMethodCardpay extends BillmateGateway {

		public function __construct()
		{
			parent::__construct();
			$this->name                 = 'billmatecardpay';
            $this->module = new BillmateGateway();
			$this->displayName          = $this->module->l('Billmate Cardpay','cardpay');
			$this->testMode             = Configuration::get('BCARDPAY_MOD');
			$this->min_value            = Configuration::get('BCARDPAY_MIN_VALUE');
			$this->max_value            = Configuration::get('BCARDPAY_MAX_VALUE');
			$this->sort_order           = (Configuration::get('BCARDPAY_SORTORDER')) ? Configuration::get('BCARDPAY_SORTORDER') : 3;
			$this->limited_countries    = array('se');
			$this->allowed_currencies   = array('SEK','EUR','DKK','NOK','GBP','USD');
			$this->authorization_method = Configuration::get('BCARDPAY_AUTHORIZATION_METHOD');
			$this->validation_controller = $this->context->link->getModuleLink('billmategateway', 'billmateapi', array('method' => 'cardpay'),true);
			$this->icon                 = file_exists(_PS_MODULE_DIR_.'billmategateway/views/img/'.Tools::strtolower($this->context->language->iso_code).'/card.png') ? 'billmategateway/views/img/'.Tools::strtolower($this->context->language->iso_code).'/card.png' : 'billmategateway/views/img/en/card.png';
		}



		/**
		 * Returns Payment info for appending in payment methods list
		 */
		public function getPaymentInfo($cart)
		{
			if (Configuration::get('BCARDPAY_ENABLED') == 0)
				return false;
			if ($this->min_value > $this->context->cart->getOrderTotal())
				return false;
			if ($this->max_value < $this->context->cart->getOrderTotal())
				return false;

			if (!in_array(strtoupper($this->context->currency->iso_code), $this->allowed_currencies))
				return false;

			return array(
				'sort_order' => $this->sort_order,
				'name'       => $this->displayName,
				'type'       => $this->name,
				'method' => 'cardpay',
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
				'name'     => 'cardpayActivated',
				'required' => false,
				'type'     => 'checkbox',
				'label'    => $this->module->l('Enabled','cardpay'),
				'desc'     => $this->module->l('Enable Billmate Card','cardpay'),
				'value'    => (Tools::safeOutput(Configuration::get('BCARDPAY_ENABLED'))) ? 1 : 0,

			);

			$settings['testmode'] = array(
				'name'     => 'cardpayTestmode',
				'required' => false,
				'type'     => 'checkbox',
				'label'    => $this->module->l('Test Mode','cardpay'),
				'desc'     => $this->module->l('Enable Test Mode','cardpay'),
				'value'    => (Tools::safeOutput(Configuration::get('BCARDPAY_MOD'))) ? 1 : 0
			);

			$settings['authorization'] = array(
				'name'    => 'cardpayAuthorization',
				'type'    => 'radio',
				'label'   => $this->module->l('Authorization Method','cardpay'),
				'desc'    => '',
				'value' => (Configuration::get('BCARDPAY_AUTHORIZATION_METHOD')) ? Configuration::get('BCARDPAY_AUTHORIZATION_METHOD') : 'authorize',
				'options' => array(
					'authorize' => $this->module->l('Authorize','cardpay'),
					'sale'      => $this->module->l('Sale','cardpay')
				)
			);

			$settings['order_status']  = array(
				'name'     => 'cardpayBillmateOrderStatus',
				'required' => true,
				'type'     => 'select',
				'label'    => $this->module->l('Set Order Status','cardpay'),
				'desc'     => $this->module->l(''),
				'value'    => (Tools::safeOutput(Configuration::get('BCARDPAY_ORDER_STATUS'))) ? Tools::safeOutput(Configuration::get('BCARDPAY_ORDER_STATUS')) : Tools::safeOutput(Configuration::get('PS_OS_PAYMENT')),
				'options'  => $statuses_array
			);
			$settings['minimum_value'] = array(
				'name'     => 'cardpayBillmateMinimumValue',
				'required' => false,
				'value'    => (float)Configuration::get('BCARDPAY_MIN_VALUE'),
				'type'     => 'text',
				'label'    => $this->module->l('Minimum Value ','cardpay').' ('.$currency->sign.')',
				'desc'     => $this->module->l(''),
			);
			$settings['maximum_value'] = array(
				'name'     => 'cardpayBillmateMaximumValue',
				'required' => false,
				'value'    => Configuration::get('BCARDPAY_MAX_VALUE') != 0 ? (float)Configuration::get('BCARDPAY_MAX_VALUE') : 99999,
				'type'     => 'text',
				'label'    => $this->module->l('Maximum Value ','cardpay').' ('.$currency->sign.')',
				'desc'     => $this->module->l(''),
			);
			$settings['sort'] = array(
				'name'     => 'cardpayBillmateSortOrder',
				'required' => false,
				'value'    => Configuration::get('BCARDPAY_SORTORDER'),
				'type'     => 'text',
				'label'    => $this->module->l('Sort Order','cardpay'),
				'desc'     => $this->module->l(''),
			);

			return $settings;

		}
	}