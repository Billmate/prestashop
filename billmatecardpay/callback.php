<?php
/**
 * Created by Boxedsolutions.
 * User: Jesper Johansson
 * Date: 2015-01-26
 * Time: 09:10
 */

$useSSL = true;
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

include_once(_PS_MODULE_DIR_.'/billmatebank/billmatebank.php');
require(_PS_MODULE_DIR_.'billmatepartpayment/backward_compatibility/backward.php');


require_once BBANK_BASE.'/Billmate.php';
require_once BBANK_BASE.'/utf8.php';
include_once(BBANK_BASE.'/xmlrpc-2.2.2/lib/xmlrpc.inc');
include_once(BBANK_BASE.'/xmlrpc-2.2.2/lib/xmlrpcs.inc');

class BillmateCallbackController extends FrontController {
	public $ssl = true;

	public function __construct()
	{
		if (version_compare(_PS_VERSION_, '1.5', '<'))
			$this->context = Context::getContext();
		if (!Configuration::get('BBANK_ACTIVE'))
			exit;
		parent::__construct();
		self::$smarty->assign('path', __PS_BASE_URI__.'modules/billmatepartpayment');
	}

	public function process()
	{
		global $country,$cookie;
		if (!Configuration::get('BBANK_ACTIVE'))
			return;
		parent::process();

		$input = Tools::file_get_contents('php://input');
		$_POST = $_GET = $_REQUEST = (array)Tools::jsonDecode($input);

		$post = $_POST;
		$cart = explode('-',$_POST['order_id']);
		$cart_id = $cart[0];
		if (Tools::getIsset('status') && !empty($post['trans_id']) && !empty($post['error_message']))
		{

			if ($post['status'] == 0)
			{
				try{
					$lockfile = _PS_CACHE_DIR_.$_POST['order_id'];
					$processing = file_exists($lockfile);
					self::$cart = new Cart($cart_id);
					if($processing || self::$cart->orderExists())
						die('OK');

					file_put_contents($lockfile, 1);
					$address_invoice = new Address((int)self::$cart->id_address_invoice);
					$country = new Country((int)$address_invoice->id_country);

					$data = $this->processReserveInvoice(Tools::strtoupper($country->iso_code),$post['order_id']);
					$billmatecardpay = new BillmateCardpay();

					$customer = new Customer((int)self::$cart->id_customer);
					$total = self::$cart->getOrderTotal();

					$extra = array('transaction_id' => $data['invoiceid']);
					$billmatecardpay->validateOrder((int)self::$cart->id, Configuration::get('BCARDPAY_ORDER_STATUS_SETTINGS'), $total, $billmatecardpay->displayName, null, $extra, null, false, $customer->secure_key);

					$result = $data['api']->UpdateOrderNo((string)$data['invoiceid'], (string)$billmatecardpay->currentOrder);

                    if (Configuration::get('BCARDPAY_AUTHMOD') == 'sale')
                        $data['api']->ActivateInvoice((string)$data['invoiceid']);

					unlink($lockfile);

				}catch(Exception $ex){
					Logger::AddLog('cb_error_message'.utf8_encode($ex->getMessage()));
				}
			}
			exit('finalize');
		}
	}

	public function processReserveInvoice($isocode, $order_id = '')
	{
		if (version_compare(_PS_VERSION_, '1.5', '<'))
			$this->context->controller->module = new BillmateBank();
		global $cookie;
		$order_id = $order_id == '' ? time(): $order_id;

		$address_delivery = new Address((int)self::$cart->id_address_delivery);
		$address_billing = new Address((int)self::$cart->id_address_invoice);
		$country = Tools::strtoupper($address_delivery->country);
		$country = new Country((int)$address_delivery->id_country);

		$countryname = BillmateCountry::getContryByNumber(BillmateCountry::fromCode($country->iso_code));
		$countryname = Tools::strtoupper($countryname);

		$eid = (int)Configuration::get('BBANK_STORE_ID_SWEDEN');
		$secret = (float)Configuration::get('BBANK_SECRET_SWEDEN');

		$ssl = true;
		$debug = false;

		$k = new BillMate($eid, $secret, $ssl, $debug, Configuration::get('BBANK_MOD'));

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

		$country = new Country((int)$address_delivery->id_country);

		$countryname = BillmateCountry::getContryByNumber(BillmateCountry::fromCode($country->iso_code));
		$countryname = Tools::strtoupper($countryname);
		$country = $countryname == 'SWEDEN' ? 209 : $countryname;

		$ship_address = array(
			'email'           => $cookie->email,
			'telno'           => '',
			'cellno'          => '',
			'fname'           => $address_delivery->firstname,
			'lname'           => $address_delivery->lastname,
			'company'         => $address_delivery->company,
			'careof'          => '',
			'street'          => $address_delivery->address1,
			'zip'             => $address_delivery->postcode,
			'city'            => $address_delivery->city,
			'country'         => (string)$countryname,
		);

		$country = new Country((int)$address_billing->id_country);

		$countryname = BillmateCountry::getContryByNumber(BillmateCountry::fromCode($country->iso_code));
		$countryname = Tools::strtoupper($countryname);
		$country = $countryname == 'SWEDEN' ? 209 : $countryname;

		$bill_address = array(
			'email'           => $cookie->email,
			'telno'           => '',
			'cellno'          => '',
			'fname'           => $address_billing->firstname,
			'lname'           => $address_billing->lastname,
			'company'         => $address_billing->company,
			'careof'          => '',
			'street'          => $address_billing->address1,
			'house_number'    => '',
			'house_extension' => '',
			'zip'             => $address_billing->postcode,
			'city'            => $address_billing->city,
			'country'         => (string)$countryname,
		);

		foreach ($ship_address as $key => $col){
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
		$goods_list = array();
		foreach ($products as $product)
		{
            $taxrate = ($product['price_wt'] == $product['price']) ? 0 : $product['rate'];
			$goods_list[] = array(
				'qty'   => (int)$product['cart_quantity'],
				'goods' => array(
					'artno'    => $product['id_product'],
					'title'    => $product['name'],
					'price'    => $product['price'] * 100,
					'vat'      => (float)$taxrate,
					'discount' => 0.0,
					'flags'    => 0,
				)
			);
			$vatrate = $taxrate;
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
					'price'    => 0 - abs($discountamount * 100),
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
			if (empty($cart_details[$total]) || $cart_details[$total] <= 0) continue;
            $flags = ($vatrate > 0) ? $flag | 32 : $flag;
			$goods_list[] = array(
				'qty'   => 1,
				'goods' => array(
					'artno'    => '',
					'title'    => isset($label[$total]) ? $label[$total] : ucwords( str_replace('_', ' ', str_replace('total_', '', $total))),
					'price'    => $cart_details[$total] * 100,
					'vat'      => (float)$vatrate,
					'discount' => 0.0,
					'flags'    => $flags,
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
			'sid'=>array('time'=>microtime(true)),
			'extraInfo'=>array(array('cust_no'=>'0' ,'creditcard_data'=> $_REQUEST))
		);

		//$transaction['extraInfo'][0]['status'] = 'Paid';

		$result1 = $k->AddInvoice('', $bill_address, $ship_address, $goods_list, $transaction);

		if (is_string($result1))
			throw new Exception(utf8_encode($result1), 122);

		return array('invoiceid' => $result1[0], 'api' => $k );

	}
}

$billmate_callback = new BillmateCallbackController();
$billmate_callback->run();