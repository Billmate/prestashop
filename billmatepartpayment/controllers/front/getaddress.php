<?php
if (version_compare(_PS_VERSION_,'1.5','<')){
	if( !class_exists('ModuleFrontController')){
		class ModuleFrontController extends FrontController{
		}
	}
}
require_once(dirname(dirname(dirname(dirname(__FILE__))))).'/billmateinvoice/commonfunctions.php';

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

class BillmatePartpaymentGetaddressModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $ajax = true;
	private function _getDb()
	{
		return array(
			'user' => _DB_USER_,
			'passwd' => _DB_PASSWD_,
			'dsn' => _DB_SERVER_,
			'db' => _DB_NAME_,
			'table' => _DB_PREFIX_.'billmate_payment_pclasses'
		);
	}
	public function ajax(){
	}
    public function init(){
		global $link;
        parent::init();

		$countries = $this->context->controller->module->countries;
		
        $adrsDelivery = new Address((int)$this->context->cart->id_address_delivery);
        $adrsBilling = new Address((int)$this->context->cart->id_address_invoice);

/*		if (!$this->context->customer->isLogged() || empty($this->context->cart)){
            die("Please login first");
        }*/
		$country = new Country((int)$adrsBilling->id_country);
///        $country = strtoupper($adrsDelivery->country);
        
        $countryname = BillmateCountry::getContryByNumber( BillmateCountry::fromCode($country->iso_code)  );
        $countryname = Tools::strtoupper($countryname);
        

        $eid = (int)Configuration::get('BILLMATE_STORE_ID_'.$countryname);
        $secret = (float)Configuration::get('BILLMATE_SECRET_'.$countryname);

        $ssl = true;
        $debug = false;

		try{
			$k = new BillMate($eid,$secret,$ssl,$debug,Configuration::get('BILLMATEINV_MOD'));
			$person = trim(Tools::getValue('billmate_pno'));
			$k->config(
				$eid,
				$secret,
				$countries[$country->iso_code]['code'],
				$countries[$country->iso_code]['langue'],
				$countries[$country->iso_code]['currency'],
				Configuration::get('BILLMATE_MOD'),
				'mysql', $this->_getDb());
				
			$addr = $k->getAddresses($person,NULL, 5);
			
			array_walk($addr[0], function(&$item){
				$item = utf8_encode($item);
			});

			if(isset($addr['error'])){
				$return = array('success' => false, 'content' =>'Betalning med Billmate misslyckades. Felkod 1001. Välj ett annat betalningssätt eller ange ett annat person/organisationsnummer.');
			//	die(Tools::jsonEncode($return));
			$message = '{success:false, content: "'.utf8_encode('Betalning med Billmate misslyckades. Felkod 1001. Välj ett annat betalningssätt eller ange ett annat person/organisationsnummer.').'"}';

			billmate_log_data($message, $eid, 'client_error_check_address');
			die($message);
			}
        }catch(Exception $ex ){
        	$return = array('success' => false, 'content' =>utf8_encode( 'Error : '.$ex->getMessage() ));
			billmate_log_data($return, $eid,'client_error_check_address');
	        die(Tools::jsonEncode($return));
        }

        $fullname = $adrsDelivery->firstname.' '.$adrsDelivery->lastname;
		
        if(strlen($addr[0]->getFirstname()) <= 0 ){
            $addr[0]->setFirstname( $adrsDelivery->firstname );
            $addr[0]->setLastname($adrsDelivery->lastname);
        }
        $apiName  = $addr[0]->getFirstname().' '.$addr[0]->getLastname();
        
        $usership = $adrsDelivery->firstname.' '.$adrsDelivery->lastname;
        $userbill = $adrsBilling->firstname.' '.$adrsBilling->lastname;
        
        $firstArr = explode(' ', $adrsDelivery->firstname);
        $lastArr  = explode(' ', $adrsDelivery->lastname);
        
        $apifirst = explode(' ', $addr[0]->getFirstname() );
        $apilast  = explode(' ', $addr[0]->getLastname() );
        
        $matchedFirst = array_intersect($apifirst, $firstArr );
        $matchedLast  = array_intersect($apilast, $lastArr );
        $apiMatchedName   = !empty($matchedFirst) && !empty($matchedLast);

        
        $address_same = matchstr( $usership, $userbill ) && 
            matchstr( $adrsBilling->city, $adrsDelivery->city ) && 
            matchstr( $adrsBilling->postcode, $adrsDelivery->postcode ) &&
            matchstr( $adrsBilling->address1, $adrsDelivery->address1 ) ;

        if( 
            !(
               $apiMatchedName 
               && matchstr( $adrsDelivery->address1, $addr[0]->getStreet() ) 
               && matchstr( $adrsDelivery->postcode ,$addr[0]->getZipCode() ) 
               && matchstr( $adrsDelivery->city ,$addr[0]->getCity()) 
               && matchstr( BillmateCountry::getContryByNumber($addr[0]->getCountry()), $countryname )
			   && $address_same
           ))
        { 
            if( Tools::getValue('geturl') == 'yes' ){
                $addressnew = new Address();
        		@$addressnew->id_customer = (int)$this->context->customer->id;
		    	$cart_details = $this->context->cart->getSummaryDetails(null, true);
				$carrier_id = $cart_details['carrier']->id;
                

				if(version_compare(_PS_VERSION_,'1.5','>=')){
					$carrier = Carrier::getCarrierByReference( $cart_details['carrier']->id_reference);
				}
			    
				$firstname = $addr[0]->getFirstname();
				
				$addressnew->company = $addr[0]->getCompanyName();
				$addressnew->firstname = $addr[0]->getFirstname();
				$addressnew->lastname = $addr[0]->getLastname();
				$addressnew->phone = $adrsBilling->phone;
				$addressnew->phone_mobile = $adrsBilling->phone_mobile;
			    $addressnew->address1 = $addr[0]->getStreet();
                $addressnew->postcode = $addr[0]->getZipCode();
                $addressnew->city = $addr[0]->getCity();
                $addressnew->country = BillmateCountry::getContryByNumber($addr[0]->getCountry());
                $addressnew->alias   = substr('Billmate Imported : '.$apiName,0,32);
                
                $addressnew->id_country = Country::getByIso(BillmateCountry::getCode($addr[0]->getCountry()));
                $addressnew->save();


				$sql = 'UPDATE `'._DB_PREFIX_.'cart_product`
				SET `id_address_delivery` = '.(int)$addressnew->id.'
				WHERE  `id_cart` = '.(int)$this->context->cart->id.'
					AND `id_address_delivery` = '.(int)$this->context->cart->id_address_delivery;
				Db::getInstance()->execute($sql);
		
				$sql = 'UPDATE `'._DB_PREFIX_.'customization`
					SET `id_address_delivery` = '.(int)$addressnew->id.'
					WHERE  `id_cart` = '.(int)$this->context->cart->id.'
						AND `id_address_delivery` = '.(int)$this->context->cart->id_address_delivery;
				Db::getInstance()->execute($sql);
        		$this->context->cart->id_address_invoice = (int)$addressnew->id;
                $this->context->cart->id_address_delivery = (int)$addressnew->id;
                $this->context->cart->update();                
				if(version_compare(_PS_VERSION_,'1.5','>=')){
					if( Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
						if(version_compare(_PS_VERSION_,'1.5','>=')){
							$carrierurl = $link->getPageLink("order-opc", true);
						} else {
							$carrierurl = $link->getPageLink("order-opc.php", true);
						}
						$return = array(
							'success' => true,
							'carrierurl' => $carrierurl,
							'action'  => array(
								'method'=> 'updateCarrierAndGetPayments' , //updateExtraCarrier
								'gift' => 0,
								'gift_message'=> '',
								'recyclable'=>0,
								'delivery_option['.$addressnew->id.']' => $carrier->id.',',
								'ajax' => true,
								'token'=> Tools::getToken(false),
							)
						);
					} else {
						if(version_compare(_PS_VERSION_,'1.5','>=')){
							$carrierurl = $link->getPageLink("order", true);
						} else {
							$carrierurl = $link->getPageLink("order.php", true);
						}
						$return = array(
							'success' => true,
							'carrierurl' => $carrierurl,
							'action'  => array(
								'method'=> 'updateExtraCarrier',
								'id_delivery_option'=> $carrier_id.',', 
								'id_carrier' => $carrier_id,
								'allow_refresh'=> 1, 
								'ajax' => true,
								'token'=> Tools::getToken(false),
							)
						);
				   }
			   }else{
				$return = array( 'success' => true );
			   }
            } else {
				$countryname = '';
				if( BillmateCountry::getCode($addr[0]->getCountry()) != 'se' ){
					$countryname = billmate_translate_country(BillmateCountry::getCode($addr[0]->getCountry()));
				}
				
				if( strlen($addr[0]->getCompanyName() )){
					$this->context->smarty->assign('firstname', $addr[0]->getCompanyName());
					$this->context->smarty->assign('lastname','');
				} else {
					$this->context->smarty->assign('firstname', $addr[0]->getFirstname());
					$this->context->smarty->assign('lastname', $addr[0]->getLastname());
				}
                $this->context->smarty->assign('address', $addr[0]->getStreet());
                $this->context->smarty->assign('zipcode', $addr[0]->getZipCode());
                $this->context->smarty->assign('city', $addr[0]->getCity());
				$this->context->smarty->assign('home_url',__PS_BASE_URI__);
				if(version_compare(_PS_VERSION_,'1.5','>=')){
					$previouslink = $link->getModuleLink('billmateinvoice', 'getaddress', array('ajax'=> 0,'clearFee' => true), true);
				} else {
					$previouslink = $link->getPageLink("order.php", true).'?step=3';
				}
				$this->context->smarty->assign('previouslink', $previouslink);
                $this->context->smarty->assign('country', $countryname);
                
                $html = $this->context->smarty->fetch(BILLMATE_BASE.'/views/templates/front/wrongaddress.tpl');
				
                $return  = array( 'success'=> false, 'content'=> $html , 'popup' => true);
           }
        } else {
            $return = array('success' => true );
        }
		if($return['success'] && !isset($return['action'])){
			$return = $this->context->controller->module->setPayment($_POST['paymentAccount']);
			unset($_SESSION['partpayment_person_nummber'], $_SESSION["uniqueId"]);
		}
        die(Tools::jsonEncode($return));
	}
/*	public function logData($merchant_id, $comment){

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
						'title'    => $this->context->controller->module->l('Rebate'),
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
					'price'    => (int) ($cart_details[$total]*100),
					'vat'      => (float)$vatrate,
					'discount' => 0.0,
					'flags'    => $flag|32,
				)
			);
		}

		billmate_log_data(
			array(
				'comment' => $comment,
				'products'=> $goods_list,
				'bill_address' => $bill_address,
				'ship_address'=> $ship_address 
			), 
			$merchant_id
		);
	}
*/
	public function postProcess()
	{

	}

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
	}
}
