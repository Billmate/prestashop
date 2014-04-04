<?php

require_once BCARDPAY_BASE. '/Billmate.php';
//error_reporting(E_ERROR);
class BillmateCardpayValidationModuleFrontController extends ModuleFrontController
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
			if ($module['name'] == 'billmatecardpay')
			{
				$authorized = true;
				break;
			}

		if (!$authorized)
			Tools::redirectLink(__PS_BASE_URI__.'order&step=3');

		$customer = new Customer($this->context->cart->id_customer);
		if (!Validate::isLoadedObject($customer))
			Tools::redirectLink(__PS_BASE_URI__.'order&step=1');
		
		if( empty($_POST)){
			$post = $_GET;
		}else{
			$post=$_POST;
		}
		$post = $_REQUEST;
	    if (isset($post['status']) && !empty($post['trans_id']) && !empty($post['error_message']))
		{
		
			$eid = (int)Configuration::get('BCARDPAY_STORE_ID_SETTINGS');
		    if( $post['status'] == 0 ){
		        try{
                	$data_return = $this->processReserveInvoice( strtoupper($this->context->country->iso_code));
					extract($data_return);
					
			        $customer = new Customer((int)$this->context->cart->id_customer);
			        $total = $this->context->cart->getOrderTotal(true, Cart::BOTH); 
					$extra = array('transaction_id'=>$invoiceid);
			        $this->module->validateOrder((int)$this->context->cart->id, Configuration::get('PS_OS_PREPARATION'), $total, $this->module->displayName, null, $extra, null, false, $customer->secure_key);
					$api->UpdateOrderNo((string)$invoiceid, $this->module->currentOrderReference.','.$this->module->currentOrder);
					unset($_SESSION["uniqueId"]);
			        Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?key='.$customer->secure_key.'&id_cart='.(int)$this->context->cart->id.'&id_module='.(int)$this->module->id.'&id_order='.(int)$this->module->currentOrder);
					
		        }catch(Exception $ex){
    		       $this->context->smarty->assign('error_message', utf8_encode($ex->getMessage())) ;
		        }
		    } else {
		       $this->context->smarty->assign('error_message', $post['error_message']) ;
		    }
		}
		$len = strlen( $post['error_message']) > 0;
		$this->context->smarty->assign('posted', $len) ;
	}

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		$this->context->smarty->assign('priceDisplayPrecision', 0);
		$this->display_column_left = false;
		parent::initContent();
		$accept_url = $this->context->link->getModuleLink('billmatecardpay', 'validation', array(), true);
		$cancel_url = $this->context->link->getPageLink('order.php', true);
		$amount     = round($this->context->cart->getOrderTotal(true, Cart::BOTH),2)*100;
		$order_id   = time();
		$currency   = $this->context->currency->iso_code;
		$languageCode= strtoupper( $this->context->language->iso_code );
		
		$languageCode = $languageCode == 'DA' ? 'DK' : $languageCode;
		$languageCode = $languageCode == 'SV' ? 'SE' : $languageCode;
		$languageCode = $languageCode == 'EN' ? 'GB' : $languageCode;
		
		$merchant_id = (int)Configuration::get('BCARDPAY_STORE_ID_SETTINGS');
		$secret = substr(Configuration::get('BCARDPAY_SECRET_SETTINGS'),0,12);
		$callback_url = 'http://api.billmate.se/callback.php';
		$do_3d_secure = Configuration::get('BILL_3DSECURE') == 'YES'? 'YES': 'NO';
		$prompt_name_entry = Configuration::get('BILL_PRNAME') == 'YES'? 'YES': 'NO';
		$return_method = strlen(Configuration::get('BCARDPAY_METHOD')) ? 'GET' : 'GET';
		
		unset($_SESSION['INVOICE_CREATED_CARD']);
        $data = array(
		    'gatewayurl' => Configuration::get('BCARDPAY_MOD') == 0 ? CARDPAY_LIVEURL : CARDPAY_TESTURL,
		    'order_id'   => $order_id,
		    'amount'     => $amount,
		    'merchant_id'=> $merchant_id,
		    'currency'   => $currency,
			'language'	 => $languageCode,
			'pay_method' => 'CARD',
		    'accept_url' => $accept_url,
			'callback_url'=> $callback_url,
			'return_method'=> $return_method,
			'capture_now' => Configuration::get('BCARDPAY_AUTHMOD') == 'sale'? 'YES': 'NO',
			'do_3d_secure' => $do_3d_secure,
			'prompt_name_entry' => $prompt_name_entry,
		    'cancel_url' => $cancel_url,
			'total'      => $this->context->cart->getOrderTotal(true, Cart::BOTH),
			'this_path'  => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		);
		$mac_str = $accept_url . $amount . $callback_url .  $cancel_url . $data['capture_now'] . $currency. $do_3d_secure . $languageCode . $merchant_id . $order_id . 'CARD' . $prompt_name_entry . $return_method. $secret;
		
		$data['mac'] = hash('sha256', $mac_str);
		$this->logData($merchant_id);
		$this->context->smarty->assign($data);
		$this->setTemplate('validation.tpl');
	}
	public function logData($merchant_id){

        $adrsDelivery = new Address((int)$this->context->cart->id_address_delivery);
        $adrsBilling = new Address((int)$this->context->cart->id_address_invoice);

        $country_to_currency = array(
            'NOR' => 'NOK',
            'SWE' => 'SEK',
            'FIN' => 'EUR',
            'DNK' => 'DKK',
            'DEU' => 'EUR',
            'NLD' => 'EUR',
        );

		$encoding = 2;
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
						'price'    => round($product['price']*100, 0),
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
			$goods_list[] = array(
				'qty'   => 1,
				'goods' => array(
					'artno'    => '',
					'title'    => isset($label[$total])? $label[$total] : ucwords( str_replace('_', ' ', str_replace('total_','', $total) ) ),
					'price'    => round($cart_details[$total]*100,0),
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
			"order1"=>(string)time(),
			'order2'=>'',
			'gender'=>'',
			"comment"=>'',
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

		if(Configuration::get('BCARDPAY_AUTHMOD') == 'sale' ) $transaction["extraInfo"][0]["status"] = 'Paid';

		$k = $this->getBillmate();
		$result1 = $k->AddOrder('',$bill_address,$ship_address,$goods_list,$transaction);  
	}

	function getBillmate(){

        $eid = (int)Configuration::get('BCARDPAY_STORE_ID_SETTINGS');
        $secret = Configuration::get('BCARDPAY_SECRET_SETTINGS');

		$ssl = true;
		$debug = false;
        
        return new BillMate($eid,$secret,$ssl,$debug,Configuration::get('BCARDPAY_MOD'));
	}
    public function processReserveInvoice( $isocode, $order_id = ''){
       	$order_id = $order_id == '' ? time(): $order_id;

        $adrsDelivery = new Address((int)$this->context->cart->id_address_delivery);
        $adrsBilling = new Address((int)$this->context->cart->id_address_invoice);
        $country = strtoupper($adrsDelivery->country);
        $countryObj = new Country(intval($adrsDelivery->id_country));
		
        $countryname = BillmateCountry::getContryByNumber( BillmateCountry::fromCode($countryObj->iso_code)  );
        $countryname = Tools::strtoupper($countryname);
        
		$k = $this->getBillmate();
	
        $personalnumber = '';
        $country_to_currency = array(
            'NOR' => 'NOK',
            'SWE' => 'SEK',
            'FIN' => 'EUR',
            'DNK' => 'DKK',
            'DEU' => 'EUR',
            'NLD' => 'EUR',
        );
		
		
		//$country = 209;
		$encoding = 2;
		
		$country = $countryname == 'SWEDEN' ? '209' : $countryname;
		
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
            'country'         => $countryname,
        );

        $country = new Country(intval($adrsBilling->id_country));
        
        $countryname = BillmateCountry::getContryByNumber( BillmateCountry::fromCode($countryObj->iso_code)  );
		$country = $countryname == 'SWEDEN' ? 209 : Tools::strtoupper($countryname);
        
        $bill_address = array(
            'email'           => $this->context->customer->email,
            'telno'           => $adrsBilling->phone,
            'cellno'          => $adrsBilling->phone_mobile,
            'fname'           => $adrsBilling->firstname,
            'lname'           => $adrsBilling->lastname,
            'company'         => $adrsBilling->company,
            'careof'          => '',
            'street'          => $adrsBilling->address1,
            'house_number'    => '1',
            'house_extension' => '',
            'zip'             => $adrsBilling->postcode,
            'city'            => $adrsBilling->city,
            'country'         => $countryname,
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
						'price'    => round($product['price']*100, 0),
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
			$goods_list[] = array(
				'qty'   => 1,
				'goods' => array(
					'artno'    => '',
					'title'    => isset($label[$total])? $label[$total] : ucwords( str_replace('_', ' ', str_replace('total_','', $total) ) ),
					'price'    => round($cart_details[$total]*100, 0),
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
			'order2'=>'',
			"flags"=>0,
			'gender'=>'',
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

		if(Configuration::get('BCARDPAY_AUTHMOD') == 'sale' ) $transaction["extraInfo"][0]["status"] = 'Paid';
		if( empty($bill_address) || empty($ship_address) || empty($goods_list)) return false;

		if( isset($_SESSION['INVOICE_CREATED_CARD']) ){
			$result1 = array($_SESSION['INVOICE_CREATED_CARD']);
		} else {
			$result1 = $k->AddInvoice('',$bill_address,$ship_address,$goods_list,$transaction);  
		}

		if(is_string($result1) || isset($result1['error']) || !is_array($result1))
		{
			throw new Exception(utf8_encode($result1), 122);
		}else{
			$_SESSION['INVOICE_CREATED_CARD'] = $result1[0];
		}
		return array('invoiceid' => $result1[0], 'api' => $k );
    }
}
