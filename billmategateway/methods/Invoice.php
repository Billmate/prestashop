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
require_once(_PS_MODULE_DIR_.'billmategateway/billmategateway.php');
require_once(_PS_MODULE_DIR_.'billmategateway/classes/BillmateInvoiceFee.php');


class BillmateMethodInvoice extends BillmateGateway {

		public function __construct()
		{
			parent::__construct();
			$this->name                 = 'billmateinvoice';
            $this->module = new BillmateGateway();
            $this->invoiceFee = new BillmateInvoiceFee();
			$this->displayName          = $this->module->l('Billmate Invoice','invoice');
			$this->testMode             = Configuration::get('BINVOICE_MOD');
			$this->min_value            = Configuration::get('BINVOICE_MIN_VALUE');
			$this->max_value            = Configuration::get('BINVOICE_MAX_VALUE');
			$this->sort_order           = (Configuration::get('BINVOICE_SORTORDER')) ? Configuration::get('BINVOICE_SORTORDER') : 0;
			$this->limited_countries    = array('se');
			$this->allowed_currencies   = array('SEK','EUR','DKK','NOK','GBP','USD');
			$this->authorization_method = false;
			$this->validation_controller = $this->context->link->getModuleLink('billmategateway', 'billmateapi', array('method' => 'invoice'),true);
			$this->icon                 = file_exists(_PS_MODULE_DIR_.'billmategateway/views/img/'.Tools::strtolower($this->context->language->iso_code).'/invoice.png') ? 'billmategateway/views/img/'.Tools::strtolower($this->context->language->iso_code).'/invoice.png' : 'billmategateway/views/img/en/invoice.png';
		}



		/**
		 * Returns Payment info for appending in payment methods list
		 */
		public function getPaymentInfo($cart)
		{
			if (Configuration::get('BINVOICE_ENABLED') == 0)
				return false;

			if ($this->min_value > $this->context->cart->getOrderTotal())
				return false;
			if ($this->max_value < $this->context->cart->getOrderTotal())
				return false;

			if (!in_array(strtoupper($this->context->currency->iso_code), $this->allowed_currencies))
				return false;
			if (!in_array(Tools::strtolower($this->context->country->iso_code), $this->limited_countries))
				return false;

			return array(
				'sort_order' => $this->sort_order,
				'name'       => $this->displayName,
				'type'       => $this->name,
				'controller' => $this->validation_controller,
				'icon'       => $this->icon,
				'agreements' => '<span>'.$this->module->l('My email is accurate and can be used for invoicing.','invoice').'<br/> <a id="terms" style="cursor:pointer!important">'.$this->module->l('I also confirm the terms for invoice payment','invoice').'</a>, <a id="billmate-privacy-policy" href="https://www.billmate.se/integritetspolicy/" target="_blank">'.$this->module->l('Privacy Policy', 'invoice').'</a> '.$this->module->l('and accept the liability.','invoice').'</span>',
				'invoiceFee' => $this->getFee()
			);
		}

		public function getSettings()
		{
			$settings = array();
			$statuses = OrderState::getOrderStates((int)$this->context->language->id);
			$currency       = Currency::getDefaultCurrency();
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
				'label'    => $this->module->l('Enabled','invoice'),
				'desc'     => $this->module->l('Enable Billmate invoice','invoice'),
				'value'    => (Tools::safeOutput(Configuration::get('BINVOICE_ENABLED'))) ? 1 : 0,

			);

			$settings['testmode'] = array(
				'name'     => 'invoiceTestmode',
				'required' => true,
				'type'     => 'checkbox',
				'label'    => $this->module->l('Test Mode','invoice'),
				'desc'     => $this->module->l('Enable Test Mode','invoice'),
				'value'    => (Tools::safeOutput(Configuration::get('BINVOICE_MOD'))) ? 1 : 0
			);

			$settings['invoice_fee'] = array(
				'name'     => 'invoiceFee',
				'required' => false,
				'value'    => (float)Configuration::get('BINVOICE_FEE'),
				'type'     => 'text',
				'label'    => $this->module->l('Invoice fee ex. VAT ','invoice'),
				'desc'     => $currency->iso_code,
			);

			$settings['invoice_fee_tax'] = array(
				'name'     => 'invoiceFeeTax',
				'required' => true,
				'type'     => 'select',
				'label'    => $this->module->l('Set Invoice Fee Tax Class','invoice'),
				'desc'     => $this->module->l(''),
				'value'    => (Tools::safeOutput(Configuration::get('BINVOICE_FEE_TAX'))) ? Tools::safeOutput(Configuration::get('BINVOICE_FEE_TAX')) : '',
				'options'  => $taxes_array
			);


			$settings['order_status']  = array(
				'name'     => 'invoiceBillmateOrderStatus',
				'required' => true,
				'type'     => 'select',
				'label'    => $this->module->l('Set Order Status','invoice'),
				'desc'     => $this->module->l(''),
				'value'    => (Tools::safeOutput(Configuration::get('BINVOICE_ORDER_STATUS'))) ? Tools::safeOutput(Configuration::get('BINVOICE_ORDER_STATUS')) : Tools::safeOutput(Configuration::get('PS_OS_PAYMENT')),
				'options'  => $statuses_array
			);
			$settings['minimum_value'] = array(
				'name'     => 'invoiceBillmateMinimumValue',
				'required' => false,
				'value'    => (float)Configuration::get('BINVOICE_MIN_VALUE'),
				'type'     => 'text',
				'label'    => $this->module->l('Minimum Value ','invoice').' ('.$currency->sign.')',
				'desc'     => $this->module->l(''),
			);
			$settings['maximum_value'] = array(
				'name'     => 'invoiceBillmateMaximumValue',
				'required' => false,
				'value'    => Configuration::get('BINVOICE_MAX_VALUE') != 0 ? (float)Configuration::get('BINVOICE_MAX_VALUE') : 99999,
				'type'     => 'text',
				'label'    => $this->module->l('Maximum Value ','invoice').' ('.$currency->sign.')',
				'desc'     => $this->module->l(''),
			);
			$settings['sort'] = array(
				'name'     => 'invoiceBillmateSortOrder',
				'required' => false,
				'value'    => Configuration::get('BINVOICE_SORTORDER'),
				'type'     => 'text',
				'label'    => $this->module->l('Sort Order','invoice'),
				'desc'     => $this->module->l(''),
			);

			return $settings;

		}

        /**
         * @return array
         */
		public function getFee()
		{
			$feeArray        = array();
			$feeArray['fee'] = (Configuration::get('BINVOICE_FEE') > 0) ? Configuration::get('BINVOICE_FEE') : 0;
            $feeArray['fee_incl_tax'] = 0;
            $feeArray['fee_incl_formatted'] = 0;
			if ($feeArray['fee'] > 0) {
				$tax           = new Tax(Configuration::get('BINVOICE_FEE_TAX'));
				$taxCalculator = new TaxCalculator(array($tax));

				$taxAmount = $taxCalculator->getTaxesAmount($feeArray['fee']);
				$currency = new Currency($this->context->currency->id);

				$feeArray['fee_tax'] = $taxAmount;

				$fee = $taxCalculator->addTaxes($feeArray['fee']);

				if ($fee > 0) {
					$feeArray['fee_incl_tax'] = Tools::convertPriceFull($fee,null,$currency);
					$feeArray['fee_incl_formatted'] = Tools::displayPrice($fee, $currency);
				}
				return $feeArray;
			}

			return $feeArray;


		}

		public function validateOrder($id_cart, $id_order_state, $amount_paid, $payment_method = 'Unknown',
                                      $message = null, $extra_vars = array(), $currency_special = null, $dont_touch_amount = false,
                                      $secure_key = false, Shop $shop = null)
        {
            $this->processAddingInvoiceFee($id_cart);

            return parent::validateOrder($id_cart, $id_order_state,
                $amount_paid, $payment_method,
                $message, $extra_vars,
                $currency_special, $dont_touch_amount,
                $secure_key, $shop); // TODO: Change the autogenerated stub
        }

        protected function processAddingInvoiceFee($id_cart)
        {
            $bmInvoiceFee = $this->getFee();
            if (array_key_exists('fee_tax', $bmInvoiceFee)) {
                if ($bmInvoiceFee['fee_tax']) {
                    $product = $this->getBmFeeProduct();
                    $cart = new Cart($id_cart);
                    $cart->updateQty(1, $product->id);
                    $cart->getPackageList(true);
                }
            }
        }

		public function getBmFeeProduct()
        {
            $bmInvoiceFee = $this->getFee();
            $invoiceFeeProduct = $this->invoiceFee->getProduct($bmInvoiceFee['fee_incl_tax']);
            return $invoiceFeeProduct;
        }
	}