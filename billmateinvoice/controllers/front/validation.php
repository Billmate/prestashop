<?php

/**
 * @since 1.5.0
 */
class BillmateInvoiceValidationModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $ajax = true;

	public function postProcess()
	{
		if ($this->context->cart->id_customer == 0 || $this->context->cart->id_address_delivery == 0 || $this->context->cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');

		// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
		$authorized = false;
		foreach (Module::getPaymentModules() as $module)
			if ($module['name'] == 'billmateinvoice')
			{
				$authorized = true;
				break;
			}
		if (!$authorized)
			die(Tools::displayError('This payment method is not available.'));

		$customer = new Customer($this->context->cart->id_customer);
		if (!Validate::isLoadedObject($customer))
			Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');
	}

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
	    global $link;
		$this->display_column_left = false;
		parent::initContent();
        $adrsDelivery = new Address((int)$this->context->cart->id_address_delivery);

        $country = new Country((int)$adrsDelivery->id_country);
        
        $countryname = BillmateCountry::getContryByNumber( BillmateCountry::fromCode($country->iso_code)  );
        $countryname = Tools::strtoupper($countryname);
        
		$id_product = Configuration::get('BM_INV_FEE_ID_'.$countryname);
		$product = new Product($id_product);
		$price   = $product->price;
		$price_wt = $price * (1 + (($product->getTaxesRate($adrsDelivery)) * 0.01));		
		$customer = new Customer($this->context->cart->id_customer);
		
		if(version_compare(_PS_VERSION_,'1.5','>=')){
			$previouslink = $link->getModuleLink('billmateinvoice', 'getaddress', array('ajax'=> 0,'clearFee' => true), true);
		} else {
			$previouslink = $link->getPageLink("order.php", true).'?step=3';
		}
		$this->context->smarty->assign('previouslink', $previouslink);

		$this->context->smarty->assign(array(
			'ps_version' => _PS_VERSION_,
			'total' => $this->context->cart->getOrderTotal(true, Cart::BOTH) + (float)$price_wt,
			'fee' =>(float)$price_wt,
			'customer_email' => $customer->email,
			'opc'=> (bool)Configuration::get('PS_ORDER_PROCESS_TYPE'),
			'this_path' => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		));
        
		$extra = '.tpl';
		if( $this->context->getMobileDevice() ) $extra = '-mobile.tpl';

		$this->setTemplate('validation'.$extra);
//		$this->setTemplate('validation.tpl');
	}
}
