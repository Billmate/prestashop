<?php

/**
 * Created by PhpStorm.
 * User: Boxedsolutions
 * Date: 2017-09-27
 * Time: 02:50
 */

require_once(_PS_MODULE_DIR_.'billmategateway/billmategateway.php');

class BillmateMethodSwish extends BillmateGateway
{
	public function __construct()
	{
		parent::__construct();
		$this->name                 = 'billmateswish';
		$this->module = new BillmateGateway();
		$this->displayName          = $this->module->l('Billmate Swish','swish');

		$this->validation_controller = $this->context->link->getModuleLink('billmategateway', 'billmateapi', array('method' => 'swish'),true);
		$this->icon                 = file_exists(_PS_MODULE_DIR_.'billmategateway/views/img/'.Tools::strtolower($this->context->language->iso_code).'/card.png') ? 'billmategateway/views/img/'.Tools::strtolower($this->context->language->iso_code).'/card.png' : 'billmategateway/views/img/en/card.png';
	}





	public function getSettings()
	{
		$settings       = array();
		$statuses       = OrderState::getOrderStates((int)$this->context->language->id);

		$statuses_array = array();
		foreach ($statuses as $status)
			$statuses_array[$status['id_order_state']] = $status['name'];




		$settings['order_status']  = array(
			'name'     => 'swishBillmateOrderStatus',
			'required' => true,
			'type'     => 'select',
			'label'    => $this->module->l('Set Order Status','swish'),
			'desc'     => $this->module->l(''),
			'value'    => (Tools::safeOutput(Configuration::get('BSWISH_ORDER_STATUS'))) ? Tools::safeOutput(Configuration::get('BSWISH_ORDER_STATUS')) : Tools::safeOutput(Configuration::get('PS_OS_PAYMENT')),
			'options'  => $statuses_array
		);


		return $settings;

	}
}
