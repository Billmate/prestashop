<?php
/*
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @version  Release: $Revision: 15821 $
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

$useSSL = true;
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

include_once(_PS_MODULE_DIR_.'/billmatecardpay/billmatecardpay.php');
require(_PS_MODULE_DIR_.'billmatepartpayment/backward_compatibility/backward.php');

//define('BCARDPAY_BASE', dirname(__FILE__));
require_once BCARDPAY_BASE.'/Billmate.php';
require_once BCARDPAY_BASE.'/utf8.php';
include_once(BCARDPAY_BASE."/xmlrpc-2.2.2/lib/xmlrpc.inc");
include_once(BCARDPAY_BASE."/xmlrpc-2.2.2/lib/xmlrpcs.inc");

class BillmateCardpayController extends FrontController
{
	public $ssl = true;

	public function __construct()
	{
		if (!Configuration::get('BCARDPAY_ACTIVE_CARDPAY'))
			exit;
		parent::__construct();
		self::$smarty->assign('path' , __PS_BASE_URI__.'modules/billmatepartpayment');
		$this->context = Context::getContext();
	}

	public function process()
	{
		global $country,$cookie;
		if (!Configuration::get('BCARDPAY_ACTIVE_CARDPAY'))
			return ;
		parent::process();
		
		/*if( empty($_POST)){
			$keys =explode(',','status,exp_year,exp_mon,order_id,error_message,amount,currency,mac,approval_code,time,test,merchant_id,pay_method,trans_id,card_no');
			$post = array_combine($keys, $_GET );
		}else{
			$post=$_POST;
		}*/
		$post = $_REQUEST;
	    if (Tools::getIsset('status') && !empty($post['trans_id']) && !empty($post['error_message']))
		{
		    if( $post['status'] == 0 ){
		        try{
					
					$address_invoice = new Address((int)self::$cart->id_address_invoice);
					$country = new Country((int)$address_invoice->id_country);

					$this->processReserveInvoice( strtoupper($country->iso_code));
					$bilmatecard = new BillmateCardpay();

			        $customer = new Customer((int)$cookie->id_customer);
			        $total = self::$cart->getOrderTotal();
			        $bilmatecard->validateOrder((int)self::$cart->id, Configuration::get('BCARDPAY_ORDER_STATUS_SETTINGS'), $total, $bilmatecard->displayName, null, array(), null, false, $customer->secure_key);
			        Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?key='.$customer->secure_key.'&id_cart='.(int)self::$cart->id.'&id_module='.(int)$bilmatecard->id.'&id_order='.(int)$bilmatecard->currentOrder);
		        }catch(Exception $ex){
    		       $this->context->smarty->assign('error_message', utf8_encode($ex->getMessage())) ;
		        }
		    } else {
		       $this->context->smarty->assign('error_message', $post['error_message']) ;
		    }
		}
		self::$smarty->assign(array(
				'nbProducts' => self::$cart->nbProducts(),
				'total' => self::$cart->getOrderTotal(),
				'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/billmatepartpayment/',
			));
	}

	public function displayContent()
	{
		global $link,$currency;
		parent::displayContent();
		$customer = new Customer((int)self::$cart->id_customer);
		$address_invoice = new Address((int)self::$cart->id_address_invoice);
		$country = new Country((int)$address_invoice->id_country);
		$currency = new Currency((int)self::$cart->id_currency);

		$accept_url =  _PS_BASE_URL_.__PS_BASE_URI__.'modules/billmatecardpay/validation.php';
		$cancel_url = $link->getPageLink('order.php', true);
		
		$amount     = round(self::$cart->getOrderTotal(),2)*100;
		$order_id   = time();
		$currency   = $currency->iso_code;
		
		$merchant_id = (int)Configuration::get('BCARDPAY_STORE_ID_SETTINGS');
		$secret = Tools::substr(Configuration::get('BCARDPAY_SECRET_SETTINGS'), 0, 12);
		$callback_url = 'http://api.billmate.se/callback.php';
		$do_3d_secure = Configuration::get('BILL_3DSECURE') == 'YES'? 'YES': 'NO';
		$prompt_name_entry = Configuration::get('BILL_PRNAME') == 'YES'? 'YES': 'NO';
		$return_method = Tools::strlen(Configuration::get('BCARDPAY_METHOD')) ? 'GET' : 'GET';
		
        $data = array(
		    'gatewayurl' => Configuration::get('BCARDPAY_MOD') == 0 ? CARDPAY_LIVEURL : CARDPAY_TESTURL,
		    'order_id'   => $order_id,
		    'amount'     => $amount,
		    'merchant_id'=> $merchant_id,
		    'currency'   => $currency,
			'pay_method' => 'CARD',
		    'accept_url' => $accept_url,
			'callback_url'=> $callback_url,
			'return_method'=> $return_method,
			'capture_now' => Configuration::get('BCARDPAY_AUTHMOD') == 'sale'? 'YES': 'NO',
			'do_3d_secure' => $do_3d_secure,
			'prompt_name_entry' => $prompt_name_entry,
		    'cancel_url' => $cancel_url,
			'total'      => self::$cart->getOrderTotal(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/billmatecardpay/'
		);
		$mac_str = $accept_url.$amount.$callback_url.$cancel_url.$data['capture_now'].$currency.$do_3d_secure.$merchant_id.$order_id.'CARD'.$prompt_name_entry.$return_method.$secret;

		$data['mac'] = hash('sha256', $mac_str);
		self::$smarty->assign($data);
		self::$smarty->display(_PS_MODULE_DIR_.'billmatecardpay/tpl/form.tpl');
		
	}
    public function processReserveInvoice( $isocode, $order_id = ''){
		if (version_compare(_PS_VERSION_,'1.5','<'))
			$this->context->controller->module = new BillmateCardpay();

		global $cookie;
       	$order_id = $order_id == '' ? time(): $order_id;

        $adrsDelivery = new Address((int)self::$cart->id_address_delivery);
        $adrsBilling = new Address((int)self::$cart->id_address_invoice);
        $country = strtoupper($adrsDelivery->country);
        $country = new Country((int)$adrsDelivery->id_country);
        
        $countryname = BillmateCountry::getContryByNumber(BillmateCountry::fromCode($country->iso_code));
        $countryname = Tools::strtoupper($countryname);
        
        $eid = (int)Configuration::get('BCARDPAY_STORE_ID_SETTINGS');
        $secret = (float)Configuration::get('BCARDPAY_SECRET_SETTINGS');

		$ssl = true;
		$debug = false;
        
        $k = new BillMate($eid, $secret, $ssl, $debug, Configuration::get('BCARDPAY_MOD'));

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
            'email'           => $cookie->email,
            'telno'           => '',
            'cellno'          => '',
            'fname'           => $adrsDelivery->firstname,
            'lname'           => $adrsDelivery->lastname,
            'company'         => $adrsDelivery->company,
            'careof'          => '',
            'street'          => $adrsDelivery->address1,
            'zip'             => $adrsDelivery->postcode,
            'city'            => $adrsDelivery->city,
            'country'         => (string)$countryname,
        );

        $country = new Country((int)$adrsBilling->id_country);
        
        $countryname = BillmateCountry::getContryByNumber(BillmateCountry::fromCode($country->iso_code));
        $countryname = Tools::strtoupper($countryname);
		$country = $countryname == 'SWEDEN' ? 209 : $countryname;
        
        $bill_address = array(
            'email'           => $cookie->email,
            'telno'           => '',
            'cellno'          => '',
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
        
        foreach ($ship_address as $key => $col)
		{
            if (!is_array($col))
                $ship_address[$key] = utf8_decode( Encoding::fixUTF8($col));

        }
        foreach ($bill_address as $key => $col)
		{
            if (!is_array($col))
                $bill_address[$key] = utf8_decode( Encoding::fixUTF8($col));

        }
        $products = self::$cart->getProducts();
    	$cart_details = self::$cart->getSummaryDetails(null, true);
    	
        $vatrate = 0;
		foreach ($products as $product)
		{
			$goods_list[] = array(
				'qty'   => (int)$product['cart_quantity'],
				'goods' => array(
					'artno'    => $product['id_product'],
					'title'    => $product['name'],
					'price'    => (int)$product['price'] * 100,
					'vat'      => (float)$product['rate'],
					'discount' => 0.0,
					'flags'    => 0,
				)
			);
                $vatrate = $product['rate'];
		}
		$carrier = $cart_details['carrier'];
		if (!empty($cart_details['total_discounts']))
		{
			$discountamount = $cart_details['total_discounts'] / (($vatrate + 100) / 100);
			$goods_list[] = array(
				'qty'   => 1,
				'goods' => array(
					'artno'    => '',
					'title'    => $this->context->controller->module->l('Rabatt'),
					'price'    => 0 - round(abs($discountamount * 100), 0),
					'vat'      => $vatrate,
					'discount' => 0.0,
					'flags'    => 0,
				)
				
			);
		}

		$totals = array('total_shipping','total_handling');
		$label =  array();
		//array('total_tax' => 'Tax :'. $cart_details['products'][0]['tax_name']);
		foreach ($totals as $total)
		{
		    $flag = $total == 'total_handling' ? 16 : ( $total == 'total_shipping' ? 8 : 0);
		    if (empty($cart_details[$total]) || $cart_details[$total] <= 0 ) continue;
			$goods_list[] = array(
				'qty'   => 1,
				'goods' => array(
					'artno'    => '',
					'title'    => isset($label[$total]) ? $label[$total] : ucwords(str_replace('_', ' ', str_replace('total_', '', $total))),
					'price'    => (int)$cart_details[$total] * 100,
					'vat'      => (float)$vatrate,
					'discount' => 0.0,
					'flags'    => $flag | 32,
				)
			);
		}
		$pclass = -1;
		$cutomerId = (int)$cookie->id_customer;
		$cutomerId = $cutomerId >0 ? $cutomerId: time();

		$transaction = array(
			'order1'=>(string)$order_id,
			'comment'=>'',
			'flags'=>0,
			'gender'=>0,
			'reference'=>'',
			'reference_code'=>'',
			'currency'=>$currency,
			'country'=>209,
			'language'=>$language,
			'pclass'=>$pclass,
			'shipInfo'=>array('delay_adjust'=>'1'),
			'travelInfo'=>array(),
			'incomeInfo'=>array(),
			'bankInfo'=>array(),
			'sid'=>array("time"=>microtime(true)),
			'extraInfo'=>array(array('cust_no'=>'0' ,'creditcard_data'=> $_REQUEST))
		);

		if(Configuration::get('BCARDPAY_AUTHMOD') == 'sale' ) $transaction['extraInfo'][0]['status'] = 'Paid';
		
		$result1 = $k->AddInvoice('',$bill_address,$ship_address,$goods_list,$transaction);  

		if(is_string($result1))
		{
			throw new Exception(utf8_encode($result1), 122);
		}
    }

}

$billmateController = new BillmateCardpayController();
$billmateController->run();
