<?php
	/**
	 * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
	 *
	 * @author    Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 * @license   OpenSource
	 */

	/*
	 * Class for BillmateInvoice related stuff
	 */

	class BillmateInvoice extends BillmateGateway {

		public function __construct()
		{
			parent::__construct();
			$this->name                 = 'billmateinvoice';
			$this->displayName          = $this->l('Billmate Invoice');
			$this->testMode             = Configuration::get('BILLMATE_INVOICE_MOD');
			$this->limited_countries    = array('sv');
			$this->allowed_currencies   = array('SEK');
			$this->authorization_method = false;
			$this->validation_controller = $this->context->link->getModuleLink('billmategateway', 'billmateapi', array('method' => 'invoice'));
			$this->icon                 = 'billmategateway/views/img/billmate_invoice_l.png';
		}

		/**
		 * Returns Payment info for appending in payment methods list
		 */
		public function getPaymentInfo($cart)
		{
			if (Configuration::get('BINVOICE_ENABLED') == 0)
				return false;

			return array(
				'name'       => $this->displayName,
				'type'       => $this->name,
				'controller' => $this->validation_controller,
				'icon'       => $this->icon,
				'agreements' => sprintf($this->l('My email is accurate and can be used for invoicing.').'<a id="terms" style="cursor:pointer!important"> '.$this->l('I confirm the terms for invoice payment').'</a>'),
				'invoiceFee' => $this->getFee()
			);
		}

		public function getSettings()
		{
			$settings = array();
			$statuses = OrderState::getOrderStates((int)$this->context->language->id);
			$currency = Currency::getCurrency((int)Configuration::get('PS_CURRENCY_DEFAULT'));
			$taxes    = Tax::getTaxes($this->context->language->id);

			$taxes_array = array();
			foreach ($taxes as $tax)
				$taxes_array[$tax['id_tax']] = $tax['name'];

			$statuses_array = array();
			foreach ($statuses as $status)
				$statuses_array[$status['id_order_state']] = $status['name'];

			$settings['activated'] = array(
				'name'     => 'invoiceActivated',
				'required' => true,
				'type'     => 'checkbox',
				'label'    => $this->l('Enabled'),
				'desc'     => $this->l('Should Billmate Invoice be Enabled'),
				'value'    => (Tools::safeOutput(Configuration::get('BINVOICE_ENABLED'))) ? 1 : 0,

			);

			$settings['testmode'] = array(
				'name'     => 'invoiceTestmode',
				'required' => true,
				'type'     => 'checkbox',
				'label'    => $this->l('Test Mode'),
				'desc'     => $this->l('Enable Test Mode'),
				'value'    => (Tools::safeOutput(Configuration::get('BINVOICE_MOD'))) ? 1 : 0
			);

			$settings['invoice_fee'] = array(
				'name'     => 'invoiceFee',
				'required' => false,
				'value'    => (float)Configuration::get('BINVOICE_FEE'),
				'type'     => 'text',
				'label'    => $this->l('Invoice Fee ').' ('.$currency['sign'].')',
				'desc'     => $this->l(''),
			);

			$settings['invoice_fee_tax'] = array(
				'name'     => 'invoiceFeeTax',
				'required' => true,
				'type'     => 'select',
				'label'    => $this->l('Set Invoice Fee Tax Class'),
				'desc'     => $this->l(''),
				'value'    => (Tools::safeOutput(Configuration::get('BINVOICE_FEE_TAX'))) ? Tools::safeOutput(Configuration::get('BINVOICE_FEE_TAX')) : '',
				'options'  => $taxes_array
			);


			$settings['order_status']  = array(
				'name'     => 'invoiceBillmateOrderStatus',
				'required' => true,
				'type'     => 'select',
				'label'    => $this->l('Set Order Status'),
				'desc'     => $this->l(''),
				'value'    => (Tools::safeOutput(Configuration::get('BINVOICE_ORDER_STATUS'))) ? Tools::safeOutput(Configuration::get('BINVOICE_ORDER_STATUS')) : Tools::safeOutput(Configuration::get('PS_OS_PAYMENT')),
				'options'  => $statuses_array
			);
			$settings['minimum_value'] = array(
				'name'     => 'invoiceBillmateMinimumValue',
				'required' => false,
				'value'    => (float)Configuration::get('BINVOICE_MIN_VALUE'),
				'type'     => 'text',
				'label'    => $this->l('Minimum Value ').' ('.$currency['sign'].')',
				'desc'     => $this->l(''),
			);
			$settings['maximum_value'] = array(
				'name'     => 'invoiceBillmateMaximumValue',
				'required' => false,
				'value'    => Configuration::get('BINVOICE_MAX_VALUE') != 0 ? (float)Configuration::get('BINVOICE_MAX_VALUE') : 99999,
				'type'     => 'text',
				'label'    => $this->l('Maximum Value ').' ('.$currency['sign'].')',
				'desc'     => $this->l(''),
			);

			return $settings;

		}

		public function getFee()
		{
			$feeArray        = array();
			$feeArray['fee'] = (Configuration::get('BINVOICE_FEE') > 0) ? Configuration::get('BINVOICE_FEE') : 0;
			if ($feeArray['fee'] > 0)
			{
				$tax           = new Tax(Configuration::get('BINVOICE_FEE_TAX'));
				$taxCalculator = new TaxCalculator(array($tax));

				$taxAmount                = $taxCalculator->getTaxesAmount($feeArray['fee']);
				$feeArray['fee_tax']      = $taxAmount;
				$feeArray['fee_incl_tax'] = $taxCalculator->addTaxes($feeArray['fee']);

				return $feeArray;
			}

			return $feeArray;


		}
	}