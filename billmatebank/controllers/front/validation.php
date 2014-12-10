<?php


require_once BBANK_BASE. '/Billmate.php';
error_reporting(E_ERROR);
ini_set('display_errors', 1);
require_once BBANK_BASE .'/lib/billmateCart.php';
//error_reporting(E_ERROR);
class BillmateBankValidationModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $ajax = true;

	public function postProcess()
	{

		if (!$this->module->active)
			Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');

		// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
		$authorized = false;
		foreach (Module::getPaymentModules() as $module)
			if ($module['name'] == 'billmatebank')
			{
				$authorized = true;
				break;
			}

		if (!$authorized)
			Tools::redirectLink(__PS_BASE_URI__.'order&step=3');

		$customer = new Customer($this->context->cart->id_customer);
		if (!Validate::isLoadedObject($customer))
			Tools::redirectLink(__PS_BASE_URI__.'order&step=1');

	    if (isset($_REQUEST['status']) && !empty($_REQUEST['trans_id']) && !empty($_REQUEST['error_message']))
		{
			$ids = explode("-",$_REQUEST['order_id']);
			if( sizeof($ids) < 2 ) return false;
			$_REQUEST['order_id'] = $ids[0];
			$_REQUEST['cart_id'] = $ids[1];
			
			$this->context->cart->id = (int)$_POST['cart_id'];
			
			$eid = (int)Configuration::get('BBANK_STORE_ID_SWEDEN');

		    if( $_REQUEST['status'] == 0 ){
		        try{

					$order = new Order($_REQUEST['order_id']);
					$orderhistory = OrderHistory::getLastOrderState((int)$_REQUEST['order_id']);

					if( $orderhistory->id != Configuration::get('BBANK_ORDER_STATUS_SWEDEN')){

						$data = $measurements = array();
						
						$t = new billmateCart();
						$t->id = $_REQUEST['order_id'];
						$timestart = $timetotalstart = microtime(true);
						$data_return = $this->processReserveInvoice( strtoupper($this->context->country->iso_code));
						$measurements['after_add_invoice'] =  microtime(true) - $timestart;
						extract($data_return);
						
						$timestart = microtime(true);
						$customer = new Customer((int)$this->context->cart->id_customer);
						$measurements['after_customer'] =  microtime(true) - $timestart;

						$timestart = microtime(true);
						$total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
						$measurements['calculatetotal'] = microtime(true) - $timestart;
						
						$timestart = microtime(true);
						$extra = array('transaction_id'=>$invoiceid);


						$t->completeOrder($extra,$this->context->cart->id);
						//$this->module->validateOrder((int)$this->context->cart->id, Configuration::get('PS_OS_PREPARATION'), $total, $this->module->displayName, null, $extra , null, false, $customer->secure_key);
						$measurements['validateorder'] = microtime(true) - $timestart;
						
						$timestart = microtime(true);
						//$api->UpdateOrderNo((string)$invoiceid, $this->module->currentOrderReference.','.$this->module->currentOrder);
						unset($_SESSION["uniqueId"]);
						$measurements['update_order_no'] = microtime(true) - $timestart;
						$duration = ( microtime(true)-$timetotalstart ) * 1000;

						//$api->stat("client_order_measurements", json_encode(array('order_id'=>$this->module->currentOrder, 'measurements'=>$measurements)), '', $duration);
					}else{
						$customer = new Customer((int)$this->context->cart->id_customer);
					}
					$this->module->currentOrder = $_REQUEST['order_id'];
					if( isset($_SESSION['billmate_order_id'])){
						unset($_SESSION['billmate_order_id']);
					}
					
			        Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?key='.$customer->secure_key.'&id_cart='.(int)$this->context->cart->id.'&id_module='.(int)$this->module->id.'&id_order='.(int)$this->module->currentOrder);
					die;
		        }catch(Exception $ex){
    		       $this->context->smarty->assign('error_message', utf8_encode($ex->getMessage())) ;
		        }
		    } else {
		       $this->context->smarty->assign('error_message', $_REQUEST['error_message']) ;
		    }
		}
		$len = strlen( $_REQUEST['error_message']) > 0;
		$this->context->smarty->assign('posted', $len) ;
	}
	public function logData($merchant_id, $order_id){
		if(isset( $_REQUEST['order_id'])) $order_id = $_REQUEST['order_id'];
		
		$timetotalstart = microtime(true);
        $adrsDelivery = new Address((int)$this->context->cart->id_address_delivery);
        $adrsBilling = new Address((int)$this->context->cart->id_address_invoice);
        $country = strtoupper($adrsDelivery->country);
        $country = new Country(intval($adrsDelivery->id_country));

        $countryname = BillmateCountry::getContryByNumber( BillmateCountry::fromCode($country->iso_code)  );
        $countryname = Tools::strtoupper($countryname);
        $country_to_currency = array(
            'NOR' => 'NOK',
            'SWE' => 'SEK',
            'FIN' => 'EUR',
            'DNK' => 'DKK',
            'DEU' => 'EUR',
            'NLD' => 'EUR',
        );
		$country = 209;
		$language = 138;
		$encoding = 2;
		$currency = 0;
		
        $country = new Country(intval($adrsDelivery->id_country));
        
        $countryname = BillmateCountry::getContryByNumber( BillmateCountry::fromCode($country->iso_code)  );
        $countryname = Tools::strtoupper($countryname);
		$country = $countryname == 'SWEDEN' ? 209 : $countryname;
		
        $ship_address = array(
            'email'           => $this->context->customer->email,
            'telno'           => $adrsDelivery->phone,
            'cellno'          => $adrsDelivery->phone_mobile,
            'fname'           => $adrsDelivery->firstname,
            'lname'           => $adrsDelivery->lastname,
            'company'         => $adrsDelivery->company,
            'careof'          => '',
            'street'          => $adrsDelivery->address1,
            'zip'             => $adrsDelivery->postcode,
            'city'            => $adrsDelivery->city,
            'country'         => (string)$countryname,
        );

        $country = new Country(intval($adrsBilling->id_country));
        
        $countryname = BillmateCountry::getContryByNumber( BillmateCountry::fromCode($country->iso_code)  );
        $countryname = Tools::strtoupper($countryname);
		$country = $countryname == 'SWEDEN' ? 209 : $countryname;
        
        $bill_address = array(
            'email'           => $this->context->customer->email,
            'telno'           => $adrsBilling->phone,
            'cellno'          => $adrsBilling->phone_mobile,
            'fname'           => $adrsBilling->firstname,
            'lname'           => $adrsBilling->lastname,
            'company'         => $adrsBilling->company,
            'careof'          => '',
            'street'          => $adrsBilling->address1,
            'house_number'    => '',
            'house_extension' => '',
            'zip'             => $adrsBilling->postcode,
            'city'            => $adrsBilling->city,
            'country'         => (string)$countryname,
        );
        
        foreach( $ship_address as $key => $col ){
            if( !is_array( $col )) {
                $ship_address[$key] = utf8_decode( Encoding::fixUTF8($col));
            }
        }
        foreach( $bill_address as $key => $col ){
            if( !is_array( $col )) {
                $bill_address[$key] = utf8_decode( Encoding::fixUTF8($col));
            }
        }
        $products = $this->context->cart->getProducts();
    	$cart_details = $this->context->cart->getSummaryDetails(null, true);
    	
        $vatrate =  0;
		foreach ($products as $product) {
			if(!empty($product['price'])){
				$goods_list[] = array(
					'qty'   => (int)$product['cart_quantity'],
					'goods' => array(
						'artno'    => $product['reference'],
						'title'    => $product['name'],
						'price'    => (int) ($product['price']*100),
						'vat'      => (float)$product['rate'],
						'discount' => 0.0,
						'flags'    => 0,
					)
				);
			}
                $vatrate = $product['rate'];
		}
		$carrier = $cart_details['carrier'];
		if( !empty($cart_details['total_discounts'])){
			$discountamount = $cart_details['total_discounts'] / (($vatrate+100)/100);
			if( !empty($discountamount)){
				$goods_list[] = array(
					'qty'   => 1,
					'goods' => array(
						'artno'    => '',
						'title'    => $this->context->controller->module->l('Rabatt'),
						'price'    => 0 - round(abs($discountamount*100),0),
						'vat'      => $vatrate,
						'discount' => 0.0,
						'flags'    => 0,
					)
					
				);
			}
		}

		$totals = array('total_shipping','total_handling');
		$label =  array();
		//array('total_tax' => 'Tax :'. $cart_details['products'][0]['tax_name']);
		foreach ($totals as $total) {
		    $flag = $total == 'total_handling' ? 16 : ( $total == 'total_shipping' ? 8 : 0);
		    if(empty($cart_details[$total]) || $cart_details[$total]<=0 ) continue;
			if( $total == 'total_shipping' && $cart_details['free_ship'] == 1 ) continue;
			if( empty($cart_details[$total]) ) {continue;}
			if($total == 'total_shipping'){
				$carrier = new Carrier($this->context->cart->id_carrier, $this->context->cart->id_lang);
				$vatrate = $carrier->getTaxesRate(new Address($this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
			}
			$goods_list[] = array(
				'qty'   => 1,
				'goods' => array(
					'artno'    => '',
					'title'    => isset($label[$total])? $label[$total] : ucwords( str_replace('_', ' ', str_replace('total_','', $total) ) ),
					'price'    => (int) ($cart_details[$total]*100),
					'vat'      => (float)$vatrate,
					'discount' => 0.0,
					'flags'    => $flag|32,
				)
			);
		}
		$pclass = -1;
		$cutomerId = (int)$this->context->cart->id_customer;
		$cutomerId = $cutomerId >0 ? $cutomerId: time();

		$transaction = array(
			"order1"=>(string)$order_id,
			"comment"=>'',
			'gender'=>'1',
			'order2' =>'',
			"flags"=>0,
			"reference"=>"",
			"reference_code"=>"",
			"currency"=>$this->context->currency->iso_code,
			"country"=>getCountryID(),
			"language"=>$this->context->language->iso_code,
			"pclass"=>$pclass,
			"shipInfo"=>array("delay_adjust"=>"1"),
			"travelInfo"=>array(),
			"incomeInfo"=>array(),
			"bankInfo"=>array(),
			"sid"=>array("time"=>microtime(true)),
			"extraInfo"=>array(array("cust_no"=>"0" ,"creditcard_data"=> $_REQUEST))
		);

		$timestart = microtime(true);
		$measurements = array();
		$k = $this->getBillmate();
		$result1 = $k->AddOrder('',$bill_address,$ship_address,$goods_list,$transaction);
		$measurements['add_order'] =  microtime(true) - $timestart;
		$duration = ( microtime(true)-$timetotalstart ) * 1000;
		$k->stat("client_bank_add_order_measurements",json_encode(array('order_id'=>$order_id, 'measurements'=>$measurements)), '', $duration);

	}

	function getBillmate(){
        $eid = (int)Configuration::get('BBANK_STORE_ID_SWEDEN');
        $secret = Configuration::get('BBANK_SECRET_SWEDEN');

		$ssl = true;
		$debug = false;
        
        return new BillMate($eid,$secret,$ssl,$debug,Configuration::get('BCARDPAY_MOD'));
	}

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
	    
		$this->display_column_left = false;
		parent::initContent();
		$accept_url = $this->context->link->getModuleLink('billmatebank', 'validation', array(), true);
		$cancel_url = $this->context->link->getModuleLink('billmatebank', 'cancelorder', array(), true);
		$amount     = round($this->context->cart->getOrderTotal(true, Cart::BOTH),2)*100;
		$order_id   = time();
		$currency   = 'SEK';//$this->context->currency->iso_code;
		$return_method  = 'GET';
		$merchant_id = (int)Configuration::get('BBANK_STORE_ID_SWEDEN');
		$secret = substr(Configuration::get('BBANK_SECRET_SWEDEN'),0,12);
		$callback_url = $this->context->link->getModuleLink('billmatebank', 'callback', array(), true);

		$t = new billmateCart();
		$t->name="billmatebank";
		$extra = array('transaction_id'=>time());
		$customer = new Customer((int)$this->context->cart->id_customer);

		if( isset($_SESSION['billmate_order_id'])){
			if(!isset($_REQUEST['pay_method'])) $t->cancelOrder($_SESSION['billmate_order_id']);
			unset($_SESSION['billmate_order_id']);
		}

		$total =  $this->context->cart->getOrderTotal(true, Cart::BOTH);
		try{
			$t->validateOrder((int)$this->context->cart->id, Configuration::get('BILLMATE_PAYMENT_PENDING'), $total, $this->module->displayName, null, $extra, null, false, $customer->secure_key);
		}catch(Exception $ex ){
			echo $ex->getMessage();
		}

		
		$order_id = $_SESSION['billmate_order_id'] = $t->currentOrder;
		
		$sendtohtml = $order_id.'-'.$this->context->cart->id;
		unset($_SESSION['INVOICE_CREATED_BANK']);
        $data = array(
		    'gatewayurl' => Configuration::get('BBANK_MOD') == 0 ?BANKPAY_LIVEURL : BANKPAY_TESTURL,
		    'order_id'   => $sendtohtml,
		    'amount'     => $amount,
		    'merchant_id'=> $merchant_id,
			'return_method'=> $return_method,
		    'currency'   => $currency,
			'pay_method' => 'BANK',
		    'accept_url' => $accept_url,
			'callback_url'=> $callback_url,
			'capture_now' => 'YES',
		    'cancel_url' => $cancel_url,
			'total'      => $total,
			'this_path'  => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		);
		$mac_str = $accept_url . $amount . $callback_url .  $cancel_url . $data['capture_now'] . $currency. $merchant_id . $sendtohtml . 'BANK' . $return_method . $secret;

		$this->logData($merchant_id,$order_id);
		
		$data['mac'] = hash('sha256', $mac_str);
		$this->context->smarty->assign($data);
		$this->setTemplate('validation.tpl');
	}
    public function processReserveInvoice( $isocode, $order_id = ''){
       	$order_id = $order_id == '' ? time(): $order_id;

        $adrsDelivery = new Address((int)$this->context->cart->id_address_delivery);
        $adrsBilling = new Address((int)$this->context->cart->id_address_invoice);
        $country = strtoupper($adrsDelivery->country);
        $country = new Country(intval($adrsDelivery->id_country));
        
        $countryname = BillmateCountry::getContryByNumber( BillmateCountry::fromCode($country->iso_code)  );
        $countryname = Tools::strtoupper($countryname);
        
        $eid = (int)Configuration::get('BBANK_STORE_ID_SWEDEN');
        $secret = Configuration::get('BBANK_SECRET_SWEDEN');

		$ssl = true;
		$debug = false;

        $k = new BillMate($eid,$secret,$ssl,$debug,Configuration::get('BBANK_MOD'));

        $personalnumber = '';
        $country_to_currency = array(
            'NOR' => 'NOK',
            'SWE' => 'SEK',
            'FIN' => 'EUR',
            'DNK' => 'DKK',
            'DEU' => 'EUR',
            'NLD' => 'EUR',
        );
		$country = 209;
		$language = 138;
		$encoding = 2;
		$currency = 0;
		
        $country = new Country(intval($adrsDelivery->id_country));
        
        $countryname = BillmateCountry::getContryByNumber( BillmateCountry::fromCode($country->iso_code)  );
        $countryname = Tools::strtoupper($countryname);
		$country = $countryname == 'SWEDEN' ? 209 : $countryname;
		
        $ship_address = array(
            'email'           => $this->context->customer->email,
            'telno'           => $adrsDelivery->phone,
            'cellno'          => $adrsDelivery->phone_mobile,
            'fname'           => $adrsDelivery->firstname,
            'lname'           => $adrsDelivery->lastname,
            'company'         => $adrsDelivery->company,
            'careof'          => '',
            'street'          => $adrsDelivery->address1,
            'zip'             => $adrsDelivery->postcode,
            'city'            => $adrsDelivery->city,
            'country'         => (string)$countryname,
        );

        $country = new Country(intval($adrsBilling->id_country));
        
        $countryname = BillmateCountry::getContryByNumber( BillmateCountry::fromCode($country->iso_code)  );
        $countryname = Tools::strtoupper($countryname);
		$country = $countryname == 'SWEDEN' ? 209 : $countryname;
        
        $bill_address = array(
            'email'           => $this->context->customer->email,
            'telno'           => $adrsBilling->phone,
            'cellno'          => $adrsBilling->phone_mobile,
            'fname'           => $adrsBilling->firstname,
            'lname'           => $adrsBilling->lastname,
            'company'         => $adrsBilling->company,
            'careof'          => '',
            'street'          => $adrsBilling->address1,
            'house_number'    => '',
            'house_extension' => '',
            'zip'             => $adrsBilling->postcode,
            'city'            => $adrsBilling->city,
            'country'         => (string)$countryname,
        );
        
        foreach( $ship_address as $key => $col ){
            if( !is_array( $col )) {
                $ship_address[$key] = utf8_decode( Encoding::fixUTF8($col));
            }
        }
        foreach( $bill_address as $key => $col ){
            if( !is_array( $col )) {
                $bill_address[$key] = utf8_decode( Encoding::fixUTF8($col));
            }
        }
        $products = $this->context->cart->getProducts();
    	$cart_details = $this->context->cart->getSummaryDetails(null, true);
    	
        $vatrate =  0;
		foreach ($products as $product) {
			if(!empty($product['price'])){
				$goods_list[] = array(
					'qty'   => (int)$product['cart_quantity'],
					'goods' => array(
						'artno'    => $product['reference'],
						'title'    => $product['name'],
						'price'    => (int)( $product['price'] * 100 ),
						'vat'      => (float)$product['rate'],
						'discount' => 0.0,
						'flags'    => 0,
					)
				);
			}
                $vatrate = $product['rate'];
		}
		$carrier = $cart_details['carrier'];
		if( !empty($cart_details['total_discounts'])){
			$discountamount = $cart_details['total_discounts'] / (($vatrate+100)/100);
			if(!empty($discountamount)){
				$goods_list[] = array(
					'qty'   => 1,
					'goods' => array(
						'artno'    => '',
						'title'    => $this->context->controller->module->l('Rabatt'),
						'price'    => 0 - round(abs($discountamount*100),0),
						'vat'      => $vatrate,
						'discount' => 0.0,
						'flags'    => 0,
					)
					
				);
			}
		}

		$totals = array('total_shipping','total_handling');
		$label =  array();
		//array('total_tax' => 'Tax :'. $cart_details['products'][0]['tax_name']);
		foreach ($totals as $total) {
		    $flag = $total == 'total_handling' ? 16 : ( $total == 'total_shipping' ? 8 : 0);
		    if(empty($cart_details[$total]) || $cart_details[$total]<=0 ) continue;
			if( $total == 'total_shipping' && $cart_details['free_ship'] == 1 ) continue;
			if(empty($cart_details[$total])) {continue;} 
			if($total == 'total_shipping'){
				$carrier = new Carrier($this->context->cart->id_carrier, $this->context->cart->id_lang);
				$vatrate = $carrier->getTaxesRate(new Address($this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
			}
			$goods_list[] = array(
				'qty'   => 1,
				'goods' => array(
					'artno'    => '',
					'title'    => isset($label[$total])? $label[$total] : ucwords( str_replace('_', ' ', str_replace('total_','', $total) ) ),
					'price'    => (int) ($cart_details[$total] *100 ),
					'vat'      => (float)$vatrate,
					'discount' => 0.0,
					'flags'    => $flag|32,
				)
			);
		}
		$pclass = -1;
		$cutomerId = (int)$this->context->cart->id_customer;
		$cutomerId = $cutomerId >0 ? $cutomerId: time();

		$transaction = array(
			"order1"=>(string)$order_id,
			"comment"=>'',
			"flags"=>0,
			"reference"=>"",
			"reference_code"=>"",
			"currency"=>$currency,
			"country"=>209,
			"language"=>$language,
			"pclass"=>$pclass,
			"shipInfo"=>array("delay_adjust"=>"1"),
			"travelInfo"=>array(),
			"incomeInfo"=>array(),
			"bankInfo"=>array(),
			"sid"=>array("time"=>microtime(true)),
			"extraInfo"=>array(array("cust_no"=>"0" ,"creditcard_data"=> $_REQUEST))
		);

		$transaction["extraInfo"][0]["status"] = 'Paid';

		if( empty($bill_address) || empty($ship_address) || empty($goods_list)) return false;
		
		if( isset($_SESSION['INVOICE_CREATED_BANK']) ){
			$result1 = array($_SESSION['INVOICE_CREATED_BANK']);
		} else {
			$result1 = $k->AddInvoice('',$bill_address,$ship_address,$goods_list,$transaction);  
		}
		if(is_string($result1) || isset($result1['error']) || !is_array($result1))
		{
			throw new Exception(utf8_encode($result1), 122);
		}else{
			$_SESSION['INVOICE_CREATED_BANK'] = $result1[0];
		}
		return array('invoiceid' => $result1[0], 'api' => $k,'eid' => $eid );
    }
}
