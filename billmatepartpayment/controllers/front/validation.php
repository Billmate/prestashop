<?php

/**
 * @since 1.5.0
 */
class BillmatePartpaymentValidationModuleFrontController extends ModuleFrontController
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

        $country = new Country(intval($adrsDelivery->id_country));
        
        $countryname = BillmateCountry::getContryByNumber( BillmateCountry::fromCode($country->iso_code)  );
        $countryname = Tools::strtoupper($countryname);
        
		$customer = new Customer($this->context->cart->id_customer);
		
		if(version_compare(_PS_VERSION_,'1.5','>=')){
			$previouslink = $link->getModuleLink('billmatepartpayment', 'getaddress', array('ajax'=> 0,'clearFee' => true), true);
		} else {
			$previouslink = $link->getPageLink("order.php", true).'?step=3';
		}
		$this->context->smarty->assign('previouslink', $previouslink);

		$total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
		$this->context->smarty->assign(
			array('total_fee' => $total)
		);
		$countries = $this->context->controller->module->countries;
		
		$monthlycost = $this->getMonthlyCoast($this->context->cart, $countries, $country);
		
		$this->context->smarty->assign('accountPrice', $monthlycost);		
		
		$this->context->smarty->assign(array(
			'ps_version' => _PS_VERSION_,
			'total' => $this->context->cart->getOrderTotal(true, Cart::BOTH),
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
	public function getMonthlyCoast($cart, $countries, $country)
	{

		$countryString  = $countries[$country->iso_code]['code'];
		$language = $countries[$country->iso_code]['langue'];
		$currency = $countries[$country->iso_code]['currency'];
        $countryname = BillmateCountry::getContryByNumber( BillmateCountry::fromCode($country->iso_code)  );
        $countryname = Tools::strtoupper($countryname);

		$eid    = Configuration::get('BILLMATE_STORE_ID_'.$countryname);
		$secret = Configuration::get('BILLMATE_SECRET_'.$countryname);
		$mode   = Configuration::get('BILLMATE_MOD');
		$this->context->smarty->assign(array(
			'eid' => $eid
		));
		$billmate = new pClasses($eid, $secret,$countryString, $language, $currency, $mode);

		$accountPrice = array();
		$pclasses = $billmate->getPClasses();

		$total = (float)$cart->getOrderTotal();

		foreach ($pclasses as $val)
			if ($val['minamount'] < $total && ($total <= $val['maxamount'] || $val['maxamount'] == 0))
				$accountPrice[$val['id']] = array('price' => BillmateCalc::calc_monthly_cost($total, $val, BillmateFlags::CHECKOUT_PAGE), 'month' => (int)$val['months'], 'description' => htmlspecialchars_decode(Tools::safeOutput($val['description'])));

		return $accountPrice;
	}
}
