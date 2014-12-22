<?php

require_once BBANK_BASE. '/Billmate.php';
require_once BBANK_BASE .'/lib/billmateCart.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
class BillmateBankCallbackModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $ajax = true;

	public function postProcess()
	{
		$this->context = Context::getContext();
		$input = file_get_contents("php://input");
	//	$input = '{"status": 0, "order_id": "66-54", "error_message": "Approved", "amount": "3250", "currency": "SEK", "mac": "fe5ad1a1851f556ef8028065f0499d9951b7b219cacd7109112dd70c955fe451", "time": "2014-05-10 07:15:56.043216", "test": "YES", "merchant_id": "7270", "pay_method": "handelsbanken", "trans_id": "800747942"}';

		
		$_POST = $_GET = $_REQUEST = (array)json_decode($input);
		$ids = explode("-",$_POST['order_id']);
		if( sizeof($ids) < 2 ) return false;
		$_POST['order_id'] = $ids[0];
		$_POST['cart_id'] = $ids[1];
		
		
		$order = new Order($_POST['order_id']);

		$total_amount = round(($_POST['amount']/100),2);

		if( ($order->total_paid == $total_amount) && ($order->module == 'billmatebank') ) {
			echo 'Updating order';
			$t = new billmateCart();
			$t->id = $_REQUEST['order_id'];
			$this->context->cart->id = (int)$_POST['cart_id'];
			
			$t->completeOrder(array(),$_POST['cart_id']);
			$this->context->cart = Cart::getCartByOrderId($_POST['order_id']);
			$this->context->cart->id = (int)$_POST['cart_id'];

			$data_return = $this->processReserveInvoice( strtoupper($this->context->country->iso_code));
			extract($data_return);
			$extra = array('transaction_id'=>$invoiceid);
			$order = new Order($_POST['order_id']);
			if( !empty($extra)){
				Db::getInstance()->update('order_payment',$extra,'order_reference="'.$order->reference.'"');
			}
		}
		exit("finalize");
	}
	
    public function processReserveInvoice( $isocode, $order_id = ''){
       	$order_id = $_POST['order_id'];

		$order = new Order($_POST['order_id']);
		
		$this->context->cart->id_address_delivery = $order->id_address_delivery;
		$this->context->cart->id_address_invoice = $order->id_address_invoice;
		$this->context->cart->id_customer = $order->id_customer;
		$this->context->customer = $customer = new Customer((int)$this->context->cart->id_customer);
		
		
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
			'gender'=>0,
			'order2'=>'',
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
		
		$result1 = $k->AddInvoice('',$bill_address,$ship_address,$goods_list,$transaction);  
		if(is_string($result1) || isset($result1['error']) || !is_array($result1))
		{
			throw new Exception(utf8_encode($result1), 122);
		}else{
			$_SESSION['INVOICE_CREATED_BANK'] = $result1[0];
		}
		return array('invoiceid' => $result1[0], 'api' => $k,'eid' => $eid );
    }
	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		$this->display_column_left = false;
	}
}