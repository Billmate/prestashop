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

	class BillmateCardpay extends BillmateGateway {

		public function __construct()
		{
			parent::__construct();
			$this->name                 = 'billmatecardpay';
			$this->displayName          = $this->l('Billmate Cardpay');
			$this->testMode             = Configuration::get('BILLMATE_CARD_MOD');
			$this->limited_countries    = array('sv');
			$this->allowed_currencies   = array('SEK');
			$this->authorization_method = Configuration::get('BCARDPAY_AUTHORIZATION_METHOD');
			$this->validation_controller = $this->context->link->getModuleLink('billmategateway', 'billmateapi', array('method' => 'cardpay'));
			$this->icon                 = 'billmategateway/views/img/billmate_cardpay_l.png';
		}

		/**
		 * Returns Payment info for appending in payment methods list
		 */
		public function getPaymentInfo($cart)
		{
			if (Configuration::get('BCARDPAY_ENABLED') == 0)
				return false;

			return array(
				'name'       => $this->displayName,
				'type'       => $this->name,
				'controller' => $this->validation_controller,
				'icon'       => $this->icon
			);
		}

		public function getSettings()
		{
			$settings       = array();
			$statuses       = OrderState::getOrderStates((int)$this->context->language->id);
			$currency       = Currency::getCurrency((int)Configuration::get('PS_CURRENCY_DEFAULT'));
			$statuses_array = array();
			foreach ($statuses as $status)
				$statuses_array[$status['id_order_state']] = $status['name'];


			$settings['activated'] = array(
				'name'     => 'cardpayActivated',
				'required' => false,
				'type'     => 'checkbox',
				'label'    => $this->l('Enabled'),
				'desc'     => $this->l('Should Billmate Cardpay be Enabled'),
				'value'    => (Tools::safeOutput(Configuration::get('BCARDPAY_ENABLED'))) ? 1 : 0,

			);

			$settings['testmode'] = array(
				'name'     => 'cardpayTestmode',
				'required' => false,
				'type'     => 'checkbox',
				'label'    => $this->l('Test Mode'),
				'desc'     => $this->l('Enable Test Mode'),
				'value'    => (Tools::safeOutput(Configuration::get('BCARDPAY_MOD'))) ? 1 : 0
			);

			$settings['3dsecure']   = array(
				'name'  => 'cardpay3dsecure',
				'type'  => 'checkbox',
				'label' => $this->l('Enable 3D secure'),
				'desc'  => '',
				'value' => (Tools::safeOutput(Configuration::get('BCARDPAY_3DSECURE'))) ? 1 : 0
			);
			$settings['promptname'] = array(
				'name'  => 'cardpayPromptname',
				'type'  => 'checkbox',
				'label' => $this->l('Prompt Name'),
				'desc'  => '',
				'value' => (Tools::safeOutput(Configuration::get('BCARDPAY_PROMPT'))) ? 1 : 0
			);

			$settings['authorization'] = array(
				'name'    => 'cardpayAuthorization',
				'type'    => 'radio',
				'label'   => $this->l('Authorization Method'),
				'desc'    => '',
				'options' => array(
					'authorize' => $this->l('Authorize'),
					'sale'      => $this->l('Sale')
				)
			);

			$settings['order_status']  = array(
				'name'     => 'cardpayBillmateOrderStatus',
				'required' => true,
				'type'     => 'select',
				'label'    => $this->l('Set Order Status'),
				'desc'     => $this->l(''),
				'value'    => (Tools::safeOutput(Configuration::get('BCARDPAY_ORDER_STATUS'))) ? Tools::safeOutput(Configuration::get('BCARDPAY_ORDER_STATUS')) : Tools::safeOutput(Configuration::get('PS_OS_PAYMENT')),
				'options'  => $statuses_array
			);
			$settings['minimum_value'] = array(
				'name'     => 'cardpayBillmateMinimumValue',
				'required' => false,
				'value'    => (float)Configuration::get('BCARDPAY_MIN_VALUE'),
				'type'     => 'text',
				'label'    => $this->l('Minimum Value ').' ('.$currency['sign'].')',
				'desc'     => $this->l(''),
			);
			$settings['maximum_value'] = array(
				'name'     => 'cardpayBillmateMaximumValue',
				'required' => false,
				'value'    => Configuration::get('BCARDPAY_MAX_VALUE') != 0 ? (float)Configuration::get('BCARDPAY_MAX_VALUE') : 99999,
				'type'     => 'text',
				'label'    => $this->l('Maximum Value ').' ('.$currency['sign'].')',
				'desc'     => $this->l(''),
			);

			return $settings;

		}
	}