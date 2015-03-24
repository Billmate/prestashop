<?php
/**
 * The file for specified the BillMate Invoice payment module
 *
 * PHP Version 5.3
 *
 * @category  Payment
 * @package   BillMate_Prestashop
 * @author    Gagan Preet <gaganpreet172@gmail.com>
 * @copyright 2013 Eminenece Technology
 * 
 * @link      http://integration.billmate.com/
 */

if (!defined('_CAN_LOAD_FILES_'))
	exit;

if(!defined('BILLMATE_BASE')){
	define('BILLMATE_BASE', dirname(__FILE__));
}
include_once(BILLMATE_BASE.'/Billmate.php');
include_once(BILLMATE_BASE.'/commonfunctions.php');
require_once(BILLMATE_BASE.'/lib/BillmateNew.php');

/**
 * BillmateInvoice class
 *
 * @category  Payment
 * @package   BillMate_Prestashop
 * @author    Gagan Preet <gaganpreet172@gmail.com>
 * @copyright 2013 Eminenece Technology
 * 
 * @link      http://integration.billmate.com/
 */

class BillmateInvoice extends PaymentModule
{
	private $_html = '';

	private $_postErrors = array();
	public $billmate;
	public $kitt;
	public $core;
	public $settings;
	public $billmate_merchant_id;
	public $billmate_secret;
	public $billmate_pending_status;
	public $billmate_accepted_status;
	public $billmate_countries;
	private $_postValidations = array();
	private $countries = array(
		'SE' => array('name' =>'SWEDEN', 'code' => 'SE', 'langue' => 'SV', 'currency' => 'SEK'),
		'NO' => array('name' =>'NORWAY', 'code' => 'NO', 'langue' => 'NB', 'currency' => 'NOK'),
		'DK' => array('name' =>'DENMARK', 'code' => 'DK', 'langue' => 'DA', 'currency' => 'DKK'),
		'FI' => array('name' =>'FINLAND', 'code' => 'FI', 'langue' => 'FI', 'currency' => 'EUR'),
		//'GB' => array('name' =>'UNITED_KINGDOM', 'code' => 'GB', 'langue' => 'GB', 'currency' => 'GBP'),
		//'US' => array('name' =>'UNITED_STATES', 'code' => 'US', 'langue' => 'US', 'currency' => 'USD'),
	);

	const RESERVED = 1;
	const SHIPPED = 2;
	const CANCEL = 3;

	/**
	 * Constructor for BillmateInvoice
	 */
	public function getCountries()
	{
		return $this->countries;
	}
	public function verifCountryAndCurrency($country, $currency)
	{

		if (!isset($this->countries[$country->iso_code]))
			return false;
		$currency_code = BillmateCurrency::fromCode($currency->iso_code);

		if ($currency_code === null)
			return false;
		//var_dump($this->countries[$country->iso_code]['currency'] , $currency_code);
		if ($this->countries[$country->iso_code]['currency'] != $currency_code)
			return false;
		if (Configuration::get('BM_INV_STORE_ID_'.$this->countries[$country->iso_code]['name']) > 0
			&& Configuration::get('BM_INV_SECRET_'.$this->countries[$country->iso_code]['name']) != '')
			return true;
		return false;
	}
	public function __construct()
	{
		$this->name = 'billmateinvoice';
		$this->moduleName='billmateinvoice';
		$this->tab = 'payments_gateways';
		$this->version = '1.36';
		$this->author  = 'Billmate AB';

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		parent::__construct();
		$this->core = null;
		$this->billmate = null;
		$this->country = null;
		$this->limited_countries = array('se', 'onl', 'dk', 'no', 'fi','gb','us'); //, 'no', 'fi', 'dk', 'de', 'nl'
        $this->verifyEmail = $this->l('My email %1$s is accurate and can be used for invoicing.').'<a id="terms" style="cursor:pointer!important"> '.$this->l('I confirm the terms for invoice payment').'</a>';
		/* The parent construct is required for translations */
		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Billmate Invoice');
		$this->description = $this->l('Accepts invoice payments by Billmate');
		$this->confirmUninstall = $this->l(
			'Are you sure you want to delete your settings?'
		);
		$this->billmate_merchant_id = Configuration::get('BILLMATE_MERCHANT_ID');
		$this->billmate_secret = Configuration::get('BILLMATE_SECRET');
		$this->billmate_countries = unserialize( Configuration::get('BILLMATE_ENABLED_COUNTRIES_LIST'));
		$this->billmate_fee = Configuration::get('BILLMATE_FEE');
		require(_PS_MODULE_DIR_.'billmatepartpayment/backward_compatibility/backward.php');
		$this->context->smarty->assign('base_dir', __PS_BASE_URI__);
	  }
    public function hookDisplayBackOfficeHeader()
    {
        if (isset($this->context->cookie->error) && strlen($this->context->cookie->error) > 2)
        {
            if (get_class($this->context->controller) == "AdminOrdersController")
            {
                $this->context->controller->errors[] = $this->context->cookie->error;
                unset($this->context->cookie->error);
                unset($this->context->cookie->error_orders);
            }
        }
        if(isset($this->context->cookie->diff) && strlen($this->context->cookie->diff) > 2){
            if (get_class($this->context->controller) == "AdminOrdersController")
            {
                $this->context->controller->errors[] = $this->context->cookie->diff;
                unset($this->context->cookie->diff);
                unset($this->context->cookie->diff_orders);
            }
        }
	    if (isset($this->context->cookie->api_error) && strlen($this->context->cookie->api_error) > 2){
		    if (get_class($this->context->controller) == "AdminOrdersController")
		    {
			    $this->context->controller->errors[] = $this->context->cookie->api_error;
			    unset($this->context->cookie->api_error);
			    unset($this->context->cookie->api_error_orders);
		    }
	    }
        if (isset($this->context->cookie->information) && strlen($this->context->cookie->information) > 2)
        {
            if (get_class($this->context->controller) == "AdminOrdersController")
            {
                $this->context->controller->warnings[] = $this->context->cookie->information;
                unset($this->context->cookie->information);
                unset($this->context->cookie->information_orders);
            }
        }
        if (isset($this->context->cookie->confirmation) && strlen($this->context->cookie->confirmation) > 2)
        {
            if (get_class($this->context->controller) == "AdminOrdersController")
            {
                $this->context->controller->confirmations[] = $this->context->cookie->confirmation;
                unset($this->context->cookie->confirmation);
                unset($this->context->cookie->confirmation_orders);
            }
        }
    }

    public function hookActionOrderStatusUpdate($params){
        $orderStatus = Configuration::get('BILLMATEINV_ACTIVATE_ON_STATUS');
        $activated = Configuration::get('BILLMATEINV_ACTIVATE');
        if($orderStatus && $activated) {
            $order_id = $params['id_order'];

            $id_status = $params['newOrderStatus']->id;
            $order = new Order($order_id);

            $payment = OrderPayment::getByOrderId($order_id);
            $orderStatus = unserialize($orderStatus);
            if ($order->module == $this->moduleName && in_array($id_status,$orderStatus)) {
                $eid = Configuration::get('BM_INV_STORE_ID_SWEDEN');
                $secret = Configuration::get('BM_INV_SECRET_SWEDEN');
                $testMode = Configuration::get('BMILLMATEINV_MOD');
                $ssl = true;
                $debug = false;



                $k = new BillMate((int)$eid, $secret, $ssl, $debug, $testMode);
                $api = new BillMateNew($eid, $secret, $ssl, $testMode, $debug);
                $invoice = $k->CheckInvoiceStatus((string)$payment[0]->transaction_id);

                if (Tools::strtolower($invoice) == 'created' || Tools::strtolower($invoice) == 'pending')
                {
                    $resultCheck = $api->getPaymentinfo(array('number' => $payment[0]->transaction_id));
                    $total = $resultCheck['Cart']['Total']['withtax'] / 100;
                    $orderTotal = $order->getTotalPaid();
                    $diff = $total - $orderTotal;
                    $diff = abs($diff);
                    if ($diff <= 1)
                    {
                        $adrsBilling = new Address($order->id_address_invoice);
                        $adrsDelivery = new Address($order->id_address_delivery);
                        $customer = new Customer($order->id_customer);
                        $country = new Country($adrsBilling->id_country);
                        $isocode = $country->iso_code;
                        $cart = Cart::getCartByOrderId($order->id);
                        $this->context->cart = $cart;
                        switch ($this->context->currency->iso_code)
                        {
                            case 'EUR':
                                $currency = 2;
                                break;
                            case 'DKK':
                                $currency = 3;
                                break;
                            case 'SEK':
                                $currency = 0;
                                break;
                            case 'NOK':
                                $currency = 1;
                                break;
                            default:
                                throw new Exception('Not a guilty currency for Billmate Invoice');
                                break;
                        }
                        switch ($isocode)
                        {
                            // Sweden
                            case 'SE':
                                $country = 209;
                                $language = 138;
                                $encoding = 2;
                                //$currency = 0;
                                break;
                            // Finland
                            case 'FI':
                                $country = 73;
                                $language = 37;
                                $encoding = 4;
                                //$currency = 2;
                                break;
                            // Denmark
                            case 'DK':
                                $country = 59;
                                $language = 27;
                                $encoding = 5;
                                //$currency = 3;
                                break;
                            // Norway
                            case 'NO':
                                $country = 164;
                                $language = 97;
                                $encoding = 3;
                                //$currency = 1;
                                break;
                            // Germany
                            case 'DE':
                                $country = 81;
                                $language = 28;
                                $encoding = 6;
                                //$currency = 2;
                                break;
                            // Netherlands
                            case 'NL':
                                $country = 154;
                                $language = 101;
                                $encoding = 7;
                                //$currency = 2;
                                break;
                        }

                        $ship_address = array(
                            'email' => $customer->email,
                            'telno' => $adrsDelivery->phone,
                            'cellno' => $adrsDelivery->phone_mobile,
                            'fname' => $adrsDelivery->firstname,
                            'lname' => $adrsDelivery->lastname,
                            'company' => $adrsDelivery->company,
                            'careof' => '',
                            'street' => $adrsDelivery->address1,
                            'house_number' => '',
                            'house_extension' => '',
                            'zip' => $adrsDelivery->postcode,
                            'city' => $adrsDelivery->city,
                            'country' => $adrsDelivery->country,
                        );

                        $bill_address = array(
                            'email' => $customer->email,
                            'telno' => $adrsBilling->phone,
                            'cellno' => $adrsBilling->phone_mobile,
                            'fname' => $adrsBilling->firstname,
                            'lname' => $adrsBilling->lastname,
                            'company' => $adrsBilling->company,
                            'careof' => '',
                            'street' => $adrsBilling->address1,
                            'house_number' => '',
                            'house_extension' => '',
                            'zip' => $adrsBilling->postcode,
                            'city' => $adrsBilling->city,
                            'country' => $adrsBilling->country,
                        );

                        foreach ($ship_address as $key => $col)
                        {
                            if (!is_array($col))
                                $ship_address[$key] = utf8_decode(Encoding::fixUTF8($col));
                        }
                        foreach ($bill_address as $key => $col)
                        {
                            if (!is_array($col))
                                $bill_address[$key] = utf8_decode(Encoding::fixUTF8($col));
                        }
                        $cart_details = $this->context->cart->getSummaryDetails(null, true);
                        $this->context->cart->update();

                        $products = $this->context->cart->getProducts();
                        $taxrate = 0;
                        $goods_list = array();
                        foreach ($products as $product)
                        {
                            if (!empty($product['price']))
                            {
                                $taxrate = ($product['price_wt'] == $product['price']) ? 0 : $product['rate'];
                                $goods_list[] = array(
                                    'qty' => (int)$product['cart_quantity'],
                                    'goods' => array(
                                        'artno' => $product['reference'],
                                        'title' => $product['name'],
                                        'price' => $product['price'] * 100,
                                        'vat' => (float)$taxrate,
                                        'discount' => 0.0,
                                        'flags' => ($product['id_product'] == Configuration::get('BM_INV_FEE_ID_SWEDEN')) ? 16 : 0,
                                    )

                                );
                            }
                        }

                        $carrier = $cart_details['carrier'];
                        if (!empty($cart_details['total_discounts']))
                        {
                            $discountamount = $cart_details['total_discounts'] / (($taxrate + 100) / 100);
                            if (!empty($discountamount)) {
                                $goods_list[] = array(
                                    'qty' => (int)1,
                                    'goods' => array(
                                        'artno' => '',
                                        'title' => $this->context->controller->module->l('Rabatt'),
                                        'price' => 0 - abs($discountamount * 100),
                                        'vat' => $taxrate,
                                        'discount' => 0.0,
                                        'flags' => 0,
                                    )

                                );
                            }
                        }
                        $notfree = !(isset($cart_details['free_ship']) && $cart_details['free_ship'] == 1);

                        if ($carrier->active && $notfree)
                        {

                            if (version_compare(_PS_VERSION_, '1.5', '<'))
                                $shippingPrice = $cart->getOrderShippingCost();
                            else
                                $shippingPrice = $cart->getTotalShippingCost();

                            $carrier = new Carrier($cart->id_carrier, $this->context->cart->id_lang);
                            if (version_compare(_PS_VERSION_, '1.5', '>='))
                                $taxrate = $carrier->getTaxesRate(new Address($this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
                            else
                                $taxrate = Tax::getCarrierTaxRate($cart->id_carrier, Configuration::get('PS_TAX_ADDRESS_TYPE'));

                            if (!empty($shippingPrice)) {
                                $shippingPrice = $shippingPrice / (1 + $taxrate / 100);
                                $goods_list[] = array(
                                    'qty' => 1,
                                    'goods' => array(
                                        'artno' => (string)$carrier->name . $cart->id_carrier,
                                        'title' => $carrier->name,
                                        'price' => $shippingPrice * 100,
                                        'vat' => (float)$taxrate,
                                        'discount' => 0.0,
                                        'flags' => 8, //16|32
                                    )
                                );
                            }
                        }
                        $pclass = -1;
                        $transaction = array(
                            'order1' => (string)$order_id,
                            'order2' => '',
                            'comment' => '',
                            'flags' => ($testMode) ? 2 : 8,
                            'gender' => (string)1,
                            'reference' => '',
                            'reference_code' => '',
                            'currency' => $currency,
                            'country' => $country,
                            'language' => $language,
                            'pclass' => $pclass,
                            'shipInfo' => array('delay_adjust' => '1'),
                            'travelInfo' => array(),
                            'incomeInfo' => array(),
                            'bankInfo' => array(),
                            'sid' => array('time' => microtime(true)),
                            'extraInfo' => array(array('cust_no' => (int)$this->context->cart->id_customer))
                        );

                        $result = $k->ActivateReservation((string)$payment[0]->transaction_id, '', $bill_address, $ship_address, $goods_list, $transaction);
                        if (is_string($result) || !is_array($result) || isset($result['error']))
                        {
                            $this->context->cookie->error = (isset($result['error'])) ? utf8_encode($result['error']) : utf8_encode($result);
                            $this->context->cookie->error_orders = isset($this->context->cookie->error_orders) ? $this->context->cookie->error_orders . ', ' . $order_id : $order_id;
                        }
                        $this->context->cookie->confirmation = !isset($this->context->cookie->confirmation_orders) ? sprintf($this->l('Order %s has been activated through Billmate.'), $order_id) . ' (<a target="_blank" href="http://online.billmate.se/faktura">' . $this->l('Open Billmate Online') . '</>)' : sprintf($this->l('The following orders has been activated through Billmate: %s'), $this->context->cookie->confirmation_orders . ', ' . $order_id) . ' (<a target="_blank" href="http://online.billmate.se">' . $this->l('Open Billmate Online') . '</a>)';
                        $this->context->cookie->confirmation_orders = isset($this->context->cookie->confirmation_orders) ? $this->context->cookie->confirmation_orders . ', ' . $order_id : $order_id;
                    }
                    elseif (isset($resultCheck['code']))
                    {
	                    if ($resultCheck['code'] == 5220) {
		                    $mode                             = $testMode ? 'test' : 'live';
		                    $this->context->cookie->api_error = ! isset( $this->context->cookie->api_error_orders ) ? sprintf( $this->l('Order %s failed to activate through Billmate. The order does not exist in Billmate Online. The order exists in (%s) mode however. Try changing the mode in the modules settings.'), $order_id, $mode ) . ' (<a target="_blank" href="http://online.billmate.se">' . $this->l('Open Billmate Online') . '</a>)' : sprintf( $this->l('The following orders failed to activate through Billmate: %s. The orders does not exist in Billmate Online. The orders exists in (%s) mode however. Try changing the mode in the modules settings.'), $this->context->cookie->api_error_orders, '. ' . $order_id, $mode ) . ' (<a target="_blank" href="http://online.billmate.se">' . $this->l('Open Billmate Online') . '</a>)';
	                    }
	                    else
		                    $this->context->cookie->api_error = $resultCheck['message'];

	                    $this->context->cookie->api_error_orders = isset($this->context->cookie->api_error_orders) ? $this->context->cookie->api_error_orders.', '.$order_id : $order_id;

                    }
                    else
                    {
                        $this->context->cookie->diff = !isset($this->context->cookie->diff_orders) ? sprintf($this->l('Order %s failed to activate through Billmate. The amounts don\'t match: %s, %s. Activate manually in Billmate Online.'),$order_id, $orderTotal, $total).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)' : sprintf($this->l('The following orders failed to activate through Billmate: %s. The amounts don\'t match. Activate manually in Billmate Online.'),$this->context->cookie->diff_orders.', '.$order_id) . ' (<a target="_blank" href="http://online.billmate.se">' . $this->l('Open Billmate Online') . '</a>)';
                        $this->context->cookie->diff_orders = isset($this->context->cookie->diff_orders) ? $this->context->cookie->diff_orders.', '.$order_id : $order_id;
                    }
                }
                elseif(Tools::strtolower($invoice) == 'paid' || Tools::strtolower($invoice) == 'factoring' || Tools::strtolower($invoice) == 'handling') {
                    $this->context->cookie->information = !isset($this->context->cookie->information_orders) ? sprintf($this->l('Order %s is already activated through Billmate.'),$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)' : sprintf($this->l('The following orders has already been activated through Billmate: %s'),$this->context->cookie->information_orders.', '.$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)';
                    $this->context->cookie->information_orders = isset($this->context->cookie->information_orders) ? $this->context->cookie->information_orders . ', ' . $order_id : $order_id;
                }
                else {
                    $this->context->cookie->error = !isset($this->context->cookie->error_orders) ? sprintf($this->l('Order %s failed to activate through Billmate.'),$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)' : sprintf($this->l('The following orders failed to activate through Billmate: %s.'),$this->context->cookie->error_orders.', '.$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)';
                    $this->context->cookie->error_orders = isset($this->context->cookie->error_orders) ? $this->context->cookie->error_orders . ', ' . $order_id : $order_id;
                }

            }
        }
        //Logger::AddLog(print_r($params,true));
    }

	/**
	 * Get the content to display on the backend page.
	 *
	 * @return string
	 */
	public function enable($forceAll = false){
		parent::enable($forceAll);
		Configuration::updateValue('BILLMATEINV_ACTIVE_INVOICE', true );
	}	
	public function disable($forceAll = false){
		parent::disable($forceAll);
		Configuration::updateValue('BILLMATEINV_ACTIVE_INVOICE', false );
	}	
	public function getContent()
	{
		$html = '';
		if (!empty($_POST) && Tools::getIsset('submitBillmate'))
		{
			$this->_postValidation();
			if (sizeof($this->_postValidations))
				$html .= $this->_displayValidation();
			if (sizeof($this->_postErrors))
				$html .= $this->_displayErrors();
		}
		$html .= $this->_displayAdminTpl();
		return $html;
	}
	/**
	 * @brief Method that will displayed all the tabs in the configurations forms
	 *
	 * @return Rendered form
	 */
	private function _displayAdminTpl()
	{
		$smarty = $this->context->smarty;

		$tab = array(
			'credential' => array(
				'title' => $this->l('Settings'),
				'content' => $this->_displayCredentialTpl(),
				'icon' => '../modules/'.$this->moduleName.'/img/icon-settings.gif',
				'tab' => 1,
				'selected' => true, //other has to be false
			),
		);

		$smarty->assign('tab', $tab);
		$smarty->assign('moduleName', $this->moduleName);
		$smarty->assign($this->moduleName.'Logo', '../modules/'.$this->moduleName.'/img/logo.png');
		$smarty->assign('js', array('../modules/'.$this->moduleName.'/js/billmate.js'));
		$smarty->assign($this->moduleName.'Css', '../modules/'.$this->moduleName.'/css/billmate.css');

		return $this->display(__FILE__, 'tpl/admin.tpl');
	}
	/**
	 * @brief Credentials Form Method
	 *
	 * @return Rendered form
	 */
	private function _displayCredentialTpl()
	{
		$smarty = $this->context->smarty;
		$activateCountry = array();
		$currency = Currency::getCurrency((int)Configuration::get('PS_CURRENCY_DEFAULT'));
		$statuses = OrderState::getOrderStates((int)$this->context->language->id);
		$statuses_array = array();
		$input_country = array();
		$countryCodes = array();
		$countryNames = array();
		foreach ($statuses as $status)
			$statuses_array[$status['id_order_state']] = $status['name'];

		foreach ($this->countries as $country)
		{
			$countryNames[$country['name']] = array('flag' => '../modules/'.$this->moduleName.'/img/flag_'.$country['name'].'.png', 'country_name' => $country['name']);
			$countryCodes[$country['code']] = $country['name'];

			$input_country[$country['name']]['eid_'.$country['name']] = array(
				'name' => 'billmateStoreId'.$country['name'],
				'required' => true,
				'value' => Tools::safeOutput(Configuration::get('BM_INV_STORE_ID_'.$country['name'])),
				'type' => 'text',
				'label' => $this->l('E-store ID'),
				'desc' => $this->l(''),
			);
			$input_country[$country['name']]['secret_'.$country['name']] = array(
				'name' => 'billmateSecret'.$country['name'],
				'required' => true,
				'value' => Tools::safeOutput(Configuration::get('BM_INV_SECRET_'.$country['name'])),
				'type' => 'text',
				'label' => $this->l('Secret'),
				'desc' => $this->l(''),
			);
			$input_country[$country['name']]['invoice_fee_'.$country['name']] = array(
				'name' => 'billmateInvoiceFee'.$country['name'],
				'required' => false,
				'value' => Tools::safeOutput(Configuration::get('BM_INV_FEE_'.$country['name'])),
				'type' => 'text',
				'label' => $this->l('Invoice Fee ex. tax').' ('.$currency['sign'].')',
				'desc' => $this->l(''),
			);
			$input_country[$country['name']]['order_status_'.$country['name']] = array(
				'name' => 'billmateOrderStatus'.$country['name'],
				'required' => true,
				'type' => 'select',
				'label' => $this->l('Set Order Status'),
				'desc' => $this->l(''),
				'value'=> (Tools::safeOutput(Configuration::get('BM_INV_ORDER_STATUS_'.$country['name']))) ? Tools::safeOutput(Configuration::get('BM_INV_ORDER_STATUS_'.$country['name'])) : Tools::safeOutput(Configuration::get('PS_OS_PAYMENT')),
				'options' => $statuses_array
			);
			$input_country[$country['name']]['minimum_value_'.$country['name']] = array(
				'name' => 'billmateMinimumValue'.$country['name'],
				'required' => false,
				'value' => (float)Configuration::get('BM_INV_MIN_VALUE_'.$country['name']),
				'type' => 'text',
				'label' => $this->l('Minimum Value ').' ('.$currency['sign'].')',
				'desc' => $this->l(''),
			);
			$input_country[$country['name']]['maximum_value_'.$country['name']] = array(
				'name' => 'billmateMaximumValue'.$country['name'],
				'required' => false,
				'value' => Configuration::get('BM_INV_MAX_VALUE_'.$country['name']) != 0 ? (float)Configuration::get('BM_INV_MAX_VALUE_'.$country['name']) : 99999,
				'type' => 'text',
				'label' => $this->l('Maximum Value ').' ('.$currency['sign'].')',
				'desc' => $this->l(''),
			);

			if (Configuration::get('BM_INV_STORE_ID_'.$country['name']))
				$activateCountry[] = $country['name'];
		}

        $activateStatuses = array();
        $activateStatuses = $activateStatuses + $statuses_array;
        $status_activate = array(
            'name' => 'billmateActivateOnOrderStatus[]',
            'id' => 'activationSelect',
            'required' => true,
            'type' => 'select_activate',
            'label' => $this->l('Order statuses for automatic order activation in Billmate Online'),
            'desc' => $this->l(''),
            'value'=> (Tools::safeOutput(Configuration::get('BILLMATEINV_ACTIVATE_ON_STATUS'))) ? unserialize(Configuration::get('BILLMATEINV_ACTIVATE_ON_STATUS')) : 0,
            'options' => $activateStatuses
        );

		$smarty->assign($this->moduleName.'FormCredential', './index.php?tab=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module='.$this->tab.'&module_name='.$this->name);
		$smarty->assign($this->moduleName.'CredentialTitle', $this->l('Location'));
		$smarty->assign($this->moduleName.'CredentialText', $this->l('In order to use the Billmate module, please select your host country and supply the appropriate credentials.'));
		$smarty->assign($this->moduleName.'CredentialFootText', $this->l('Please note: The selected currency and country must match the customers\' registration').'<br/>'.
			$this->l('E.g. Swedish customer, SEK, Sweden and Swedish.').'<br/>'.
			$this->l('In order for your customers to use Billmate, your customers must be located in the same country in which your e-store is registered.'));

        if(version_compare(_PS_VERSION_, '1.5', '>='))
            $showActivate = true;
        else
            $showActivate = false;


        $smarty->assign(array(
                'show_activate' => $showActivate,
                'billmate_activation' => Configuration::get('BILLMATEINV_ACTIVATE'),
                'status_activate' => $status_activate,
				'billmate_mod' => Configuration::get('BILLMATEINV_MOD'),
				'billmate_active_invoice' => Configuration::get('BILLMATEINV_ACTIVE_INVOICE'),
				'credentialInputVar' => $input_country,
				'countryNames' => $countryNames,
				'countryCodes' => $countryCodes,
				'img' => '../modules/'.$this->moduleName.'/img/',
				'activateCountry' => $activateCountry));
		return $this->display(__FILE__, 'tpl/credential.tpl');
	}

	/**
	 * @brief Error display Method
	 *
	 * @return
	 */
	private function _displayErrors()
	{
		$this->context->smarty->assign('billmateError', $this->_postErrors);
		return $this->display(__FILE__, 'tpl/error.tpl');
	}

	/**
	 * @brief Validate Method
	 *
	 * @return update the module depending on billmate webservices
	 */

	private function _postValidation()
	{
		if (Tools::getValue('billmate_mod') == 'live')
			Configuration::updateValue('BILLMATEINV_MOD', 0);
		else
			Configuration::updateValue('BILLMATEINV_MOD', 1);


		if (Tools::getIsset('billmate_active_invoice') && Tools::getValue('billmate_active_invoice'))
			Configuration::updateValue('BILLMATEINV_ACTIVE_INVOICE', true);
		else
			billmate_deleteConfig('BILLMATEINV_ACTIVE_INVOICE');

		if(Tools::getValue('billmate_activation') == 1)
			Configuration::updateValue('BILLMATEINV_ACTIVATE_ON_STATUS',serialize(Tools::getValue('billmateActivateOnOrderStatus')));

        Configuration::updateValue('BILLMATEINV_ACTIVATE',Tools::getValue('billmate_activation'));

		foreach ($this->countries as $country)
		{
			billmate_deleteConfig('BM_INV_STORE_ID_'.$country['name']);
			billmate_deleteConfig('BM_INV_SECRET_'.$country['name']);
		}

		$category_id = Configuration::get('BM_INV_CATEID');

		if (!empty($category_id))
		{
			$sql = 'SELECT COUNT(*) FROM '._DB_PREFIX_.'category where id_category="'.$category_id.'"';
			if( Db::getInstance()->getValue($sql) <= 0){
				$category_id = '';
			}
		}

		if (empty($category_id))
		{
			$category = new Category();
			$top_category = Category::getRootCategory();
			$category->id_parent = $top_category->id;
			$category->id_category_default = $top_category->id;
			$category->is_root_category = true;
			
			$languages = Language::getLanguages(false);
			$category->active = false;
			
			foreach ($languages as $language)
			{
				$category->name[$language['id_lang']] = 'Category '.$this->getFeeLabel('');
				$category->link_rewrite[$language['id_lang']] = 'billmate_invoice_fee';
			}
			if ($category->add())
			{
				Configuration::updateValue('BM_INV_CATEID', $category->id);
				$category_id = $category->id;
			}
		}
		
		foreach ($this->countries as $key => $country)
		{
			if (Tools::getIsset('activate'.$country['name']))
			{
				$storeId = (int)Tools::getValue('billmateStoreId'.$country['name']);
				$secret = pSQL(Tools::getValue('billmateSecret'.$country['name']));

				Configuration::updateValue('BM_INV_STORE_ID_'.$country['name'], $storeId);
				Configuration::updateValue('BM_INV_SECRET_'.$country['name'], $secret);
				Configuration::updateValue('BM_INV_FEE_'.$country['name'], (float)Tools::getValue('billmateInvoiceFee'.$country['name']));
				Configuration::updateValue('BM_INV_ORDER_STATUS_'.$country['name'], (int)(Tools::getValue('billmateOrderStatus'.$country['name'])));
				Configuration::updateValue('BM_INV_MIN_VALUE_'.$country['name'], (float)Tools::getValue('billmateMinimumValue'.$country['name']));
				Configuration::updateValue('BM_INV_MAX_VALUE_'.$country['name'], (Tools::getValue('billmateMaximumValue'.$country['name']) != 0 ? (float)Tools::getValue('billmateMaximumValue'.$country['name']) : 99999));
				$id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product_lang` WHERE `name` = \''.$this->getFeeLabel($country['name']).'\'');

				$taxeRules = TaxRulesGroup::getAssociatedTaxRatesByIdCountry(Country::getByIso($key));
				$maxiPrice = 0;
				$idTaxe = 0;
				foreach ($taxeRules as $key => $val)
					if ((int)$val > $maxiPrice)
					{
						$maxiPrice = (int)$val;
						$idTaxe = $key;
					}


				if ($id_product != null)
				{
					$productInvoicefee = new Product((int)$id_product);
					$productInvoicefee->id_category_default = $productInvoicefee->default_category = $category_id;
					//$productInvoicefee->addToCategories((int)$category_id);
					$productInvoicefee->price = (float)Tools::getValue('billmateInvoiceFee'.$country['name']);
					if (_PS_VERSION_ >= 1.5)
						StockAvailable::setProductOutOfStock((int)$productInvoicefee->id, false, null, 0);
					if ($idTaxe != 0)
						$productInvoicefee->id_tax_rules_group = (int)$idTaxe;
					$productInvoicefee->update();
				}
				else
				{
					$productInvoicefee = new Product();
					$productInvoicefee->out_of_stock = 1;
					$productInvoicefee->available_for_order = true;
					$productInvoicefee->id_category_default = $productInvoicefee->default_category = $category_id;
					//$productInvoicefee->addToCategories($category_id);
					$productInvoicefee->id_tax_rules_group = 0;
					$languages = Language::getLanguages(false);
					foreach ($languages as $language)
					{
						$productInvoicefee->name[$language['id_lang']] = $this->getFeeLabel($country['name']);
						$productInvoicefee->link_rewrite[$language['id_lang']] = 'invoiceFee'.$country['name'];
					}
					$productInvoicefee->price = (float)Tools::getValue('billmateInvoiceFee'.$country['name']);
					if (_PS_VERSION_ >= 1.5)
						$productInvoicefee->active = true;
					$productInvoicefee->add();
					if (_PS_VERSION_ >= 1.5)
						StockAvailable::setProductOutOfStock((int)$productInvoicefee->id, false, null, 0);
				}
				Db::getInstance()->delete('category_product', 'id_product = '.(int)$productInvoicefee->id);
				$product_cats = array(
					'id_category' => (int)$category_id,
					'id_product' => (int)$productInvoicefee->id,
					'position' => (int)1,
				);
				if (version_compare(_PS_VERSION_,'1.5','>'))
					StockAvailable::setQuantity(Tools::getValue((int)$productInvoicefee->id), '', 10000, (int)Configuration::get('PS_SHOP_DEFAULT'));

				$db = Db::getInstance();
				if ((version_compare(_PS_VERSION_,'1.5','>')))
					Db::getInstance()->insert('category_product', $product_cats,false,true,Db::INSERT_IGNORE);
				else
				{
					$row = $db->getRow('select id_category from `'._DB_PREFIX_.'category_product` where id_product="'.$productInvoicefee->id.'"');
					if(!is_array( $row ) || !isset($row['id_category'])){
						$result =& $db->Execute('insert into `'._DB_PREFIX_.'category_product` SET id_category="'.$category_id.'", id_product="'.$productInvoicefee->id.'",position="1"');
					}
				}
				
				Configuration::updateValue('BM_INV_FEE_ID_'.$country['name'], $productInvoicefee->id);
				$this->_postValidations[] = $this->l('Your account has been updated to be used in ').$country['name'];

			
			}
		} 
	}
	public function getFeeLabel($country)
	{
		$countrie = array('SWEDEN' => 'Billmate fakturaavgift');
		if( empty($countrie[$country])){
			return $this->l('Billmate Invoice Fee - '.$country);
		}
		return $countrie[$country];
	}
	/**
	 * @brief Validation display Method
	 *
	 * @return
	 */
	private function _displayValidation()
	{
		$this->context->smarty->assign('billmateValidation', $this->_postValidations);
		return $this->display(__FILE__, 'tpl/validation.tpl');
	}
	/******************************************************************/
	/** add payment state ***********************************/
	/******************************************************************/
	private function addState($en, $color)
	{
		$orderState = new OrderState();
		$orderState->name = array();
		foreach (Language::getLanguages() as $language)
			$orderState->name[$language['id_lang']] = $en;
		$orderState->send_email = false;
		$orderState->color = $color;
		$orderState->hidden = false;
		$orderState->delivery = false;
		$orderState->logable = true;
		if ($orderState->add())
			copy(dirname(__FILE__).'/logo.gif', dirname(__FILE__).'/../../img/os/'.(int)$orderState->id.'.gif');
		return $orderState->id;
	}

	/**
	 * Install the Billmate Invoice module
	 *
	 * @return bool
	 */
	public function install()
	{
		if (!parent::install() || !$this->registerHook('payment') || !$this->registerHook('adminOrder') || !$this->registerHook('paymentReturn') || !$this->registerHook('orderConfirmation'))
			return false;


		$this->registerHook('displayPayment');
        $this->registerHook('actionOrderStatusUpdate');
		if (!Configuration::get('BILLMATE_PAYMENT_ACCEPTED'))
			Configuration::updateValue('BILLMATE_PAYMENT_ACCEPTED', $this->addState('Billmate : Payment accepted', '#DDEEFF'));
		if (!Configuration::get('BILLMATE_PAYMENT_PENDING'))
			Configuration::updateValue('BILLMATE_PAYMENT_PENDING', $this->addState('Billmate : payment in pending verification', '#DDEEFF'));

        $include = array();
        $hooklists = Hook::getHookModuleExecList('displayBackOfficeHeader');
        foreach($hooklists as $hooklist)
        {
            if(!in_array($hooklist['module'],array('billmatebank','billmatecardpay','billmatepartpayment'))){
                $include[] = true;
            }
        }
        if(in_array(true,$include))
            $this->registerHook('displayBackOfficeHeader');

		/*auto install currencies*/
		$currencies = array(
			'Euro' => array('iso_code' => 'EUR', 'iso_code_num' => 978, 'symbole' => '€', 'format' => 2),
			'Danish Krone' => array('iso_code' => 'DKK', 'iso_code_num' => 208, 'symbole' => 'DAN kr.', 'format' => 2),
			'krone' => array('iso_code' => 'NOK', 'iso_code_num' => 578, 'symbole' => 'NOK kr', 'format' => 2),
			'Krona' => array('iso_code' => 'SEK', 'iso_code_num' => 752, 'symbole' => 'SEK kr', 'format' => 2)
		);

		$languages = array(
			'Swedish' => array('iso_code' => 'se', 'language_code' => 'sv', 'date_format_lite' => 'Y-m-d', 'date_format_full' => 'Y-m-d H:i:s' , 'flag' => 'sweden.png'),
			'Online' => array('iso_code' => 'onl', 'language_code' => 'onl', 'date_format_lite' => 'Y-m-d', 'date_format_full' => 'Y-m-d H:i:s', 'flag' => 'sweden.png'),
			'Deutsch' => array('iso_code' => 'de', 'language_code' => 'de', 'date_format_lite' => 'Y-m-d', 'date_format_full' => 'Y-m-d H:i:s' , 'flag' => 'germany.png'),
			'Dutch' => array('iso_code' => 'nl', 'language_code' => 'nl', 'date_format_lite' => 'Y-m-d', 'date_format_full' => 'Y-m-d H:i:s' , 'flag' => 'netherlands.png'),
			'Finnish' => array('iso_code' => 'fi', 'language_code' => 'fi', 'date_format_lite' => 'Y-m-d', 'date_format_full' => 'Y-m-d H:i:s' , 'flag' => 'finland.jpg'),
			'Norwegian' => array('iso_code' => 'no', 'language_code' => 'no', 'date_format_lite' => 'Y-m-d', 'date_format_full' => 'Y-m-d H:i:s' , 'flag' => 'norway.png'),
			'Danish' => array('iso_code' => 'da', 'language_code' => 'da', 'date_format_lite' => 'Y-m-d', 'date_format_full' => 'Y-m-d H:i:s' , 'flag' => 'denmark.png'),
		);

		foreach ($currencies as $key => $val)
		{
			if (_PS_VERSION_ >= 1.5)
				$exists = Currency::exists($val['iso_code_num'], $val['iso_code_num']);
			else
				$exists = Currency::exists($val['iso_code_num']);
			if (!$exists)
			{
				$currency = new Currency();
				$currency->name = $key;
				$currency->iso_code = $val['iso_code'];
				$currency->iso_code_num = $val['iso_code_num'];
				$currency->sign = $val['symbole'];
				$currency->conversion_rate = 1;
				$currency->format = $val['format'];
				$currency->decimals = 1;
				$currency->active = true;
				$currency->add();
			}
		}

		Currency::refreshCurrencies();
		
		$version = str_replace('.', '', _PS_VERSION_);
		$version = Tools::substr($version, 0, 2);
		
		foreach ($this->countries as $key => $val)
		{
			$country = new Country(Country::getByIso($key));
			$country->active = true;
			$country->update();
		}
		/* The hook "displayMobileHeader" has been introduced in v1.5.x - Called separately to fail silently if the hook does not exist */

		return true;
	}

	public function hookOrderConfirmation($params){
		global $smarty;

	}

	/**
	 * Hook the payment module to be shown in the payment selection page
	 *
	 * @param array $params The Smarty instance params
	 *
	 * @return string
	 */
	public function hookdisplayPayment($params)
	{
		return $this->hookPayment($params);
	}

	public function hookPayment($params)
	{
		global $smarty, $link;
		if ( !Configuration::get('BILLMATEINV_ACTIVE_INVOICE'))
			return false;
		$_SESSION['billmate'] = array();
		$total = $this->context->cart->getOrderTotal();

		$cart = $params['cart'];
		$address = new Address((int)$cart->id_address_delivery);
		$country = new Country((int)$address->id_country);

		$countryname = BillmateCountry::getContryByNumber(BillmateCountry::fromCode($country->iso_code));
		$countryname = Tools::strtoupper($countryname);
		$this->context->cart->deleteProduct((int)Configuration::get('BM_INV_FEE_ID_'.$countryname));

		$minVal = Configuration::get('BM_INV_MIN_VALUE_'.$countryname);
		$maxVal = Configuration::get('BM_INV_MAX_VALUE_'.$countryname);


		if (version_compare(_PS_VERSION_, '1.5', '>='))
			$moduleurl = $link->getModuleLink('billmateinvoice', 'validation', array(), true);
		else
			$moduleurl = __PS_BASE_URI__.'modules/billmateinvoice/validation.php?type=invoice';
		$price_wt = 0;
		if (version_compare(_PS_VERSION_, '1.5', '>='))
		{
			$id_product = Configuration::get('BM_INV_FEE_ID_'.$countryname);
			$adrsDelivery = new Address((int)$this->context->cart->id_address_delivery);
			$product = new Product($id_product);
			$price   = $product->price;
			$price_wt = $price * (1 + (($product->getTaxesRate($adrsDelivery)) * 0.01));


		}
		else
		{
			$id_product = Configuration::get('BM_INV_FEE_ID_'.$countryname);
			$product = new Product($id_product);
			$price   = $product->price;
			$price_wt = $price * (1 + ((Tax::getProductTaxRate($product->id, $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')})) * 0.01));
		}
		$this->context->smarty->assign('invoiceFee',Tools::convertPrice($price_wt,Currency::getCurrencyInstance((int)$this->context->cart->id_currency),true));
		$this->context->smarty->assign('moduleurl', $moduleurl);

		if ($total > $minVal && $total < $maxVal)
			if (version_compare(_PS_VERSION_,'1.6','>='))
			{
				$this->context->smarty->assign('invoicefeestring', $this->l(' invoice fee is added to your order'));
				return $this->display(__FILE__, '/views/templates/front/billmateinvoice.tpl');
			}
			else
				return $this->display(__FILE__, 'billmateinvoice-legacy.tpl');
		else
			return false;

	}

	/**
	 * Hook the payment module to display its confirmation template upon
	 * a successful purchase
	 *
	 * @param array $params The Smarty instance params
	 *
	 * @return string
	 */
	public function hookPaymentReturn($params)
	{
		if (version_compare(_PS_VERSION_, '1.5', '<'))
			return $this->display(dirname(__FILE__).'/', 'tpl/order-confirmation.tpl');
		else
			return $this->display(__FILE__,'confirmation.tpl');

	}
}
