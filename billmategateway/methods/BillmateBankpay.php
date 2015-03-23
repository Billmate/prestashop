<?php
/**
 * Created by PhpStorm.
 * User: jesper
 * Date: 15-03-17
 * Time: 13:01
 */

class BillmateBankpay extends BillmateGateway{

	public function __construct()
	{
		parent::__construct();
		$this->name = 'billmatebankpay';
		$this->testMode = Configuration::get('BILLMATE_BANK_MOD');
		$this->displayName = $this->l('Billmate Bankpay');
		$this->limited_countries = array('sv');
		$this->allowed_currencies = array('SEK');
		$this->authorization_method = Configuration::get('BBANKPAY_AUTHORIZATION_METHOD');
		$this->validationController = $this->context->link->getModuleLink('billmategateway','billmateapi',array('method' => 'bankpay'));
		$this->icon = 'billmategateway/images/billmate_bankpay_l.png';
	}

	/**
	 * Returns Payment info for appending in payment methods list
	 */
	public function getPaymentInfo($cart)
	{
		if(Configuration::get('BBANKPAY_ENABLED') == 0)
			return false;
		return array('name' => $this->displayName,
		             'type' => $this->name,
					 'controller' => $this->validationController,
					 'icon' => $this->icon
			);
	}

	public function getSettings()
	{
		$statuses = OrderState::getOrderStates((int)$this->context->language->id);
		$currency = Currency::getCurrency((int)Configuration::get('PS_CURRENCY_DEFAULT'));
		$statuses_array = array();
		foreach ($statuses as $status)
			$statuses_array[$status['id_order_state']] = $status['name'];

		$settings['activated'] = array(
			'name' => 'bankpayActivated',
			'required' => true,
			'type' => 'checkbox',
			'label' => $this->l('Enabled'),
			'desc' => $this->l('Should Billmate Bank be Enabled'),
			'value' => (Tools::safeOutput(Configuration::get('BBANK_ENABLED'))) ? 1 : 0,

		);

		$settings['testmode'] = array(
			'name' => 'bankpayTestmode',
			'required' => true,
			'type' => 'checkbox',
			'label' => $this->l('Test Mode'),
			'desc' => $this->l('Enable Test Mode'),
			'value' => (Tools::safeOutput(Configuration::get('BBANK_MOD'))) ? 1 : 0
		);
		$settings['authorization'] = array(
			'name' => 'bankpayAuthorization',
			'type' => 'radio',
			'label' => $this->l('Authorization Method'),
			'desc' => '',
			'options' => array(
				'authorize' => $this->l('Authorize'),
				'sale' => $this->l('Sale')
			)
		);

		$settings['order_status'] = array(
			'name' => 'bankpayBillmateOrderStatus',
			'required' => true,
			'type' => 'select',
			'label' => $this->l('Set Order Status'),
			'desc' => $this->l(''),
			'value'=> (Tools::safeOutput(Configuration::get('BBANK_ORDER_STATUS'))) ? Tools::safeOutput(Configuration::get('BBANK_ORDER_STATUS')) : Tools::safeOutput(Configuration::get('PS_OS_PAYMENT')) ,
			'options' => $statuses_array
		);
		$settings['minimum_value'] = array(
			'name' => 'bankpayBillmateMinimumValue',
			'required' => false,
			'value' => (float)Configuration::get('BBANK_MIN_VALUE'),
			'type' => 'text',
			'label' => $this->l('Minimum Value ').' ('.$currency['sign'].')',
			'desc' => $this->l(''),
		);
		$settings['maximum_value'] = array(
			'name' => 'bankpayBillmateMaximumValue',
			'required' => false,
			'value' => Configuration::get('BBANK_MAX_VALUE') != 0 ? (float)Configuration::get('BBANK_MAX_VALUE') : 99999,
			'type' => 'text',
			'label' => $this->l('Maximum Value ').' ('.$currency['sign'].')',
			'desc' => $this->l(''),
		);

		return $settings;

	}

}