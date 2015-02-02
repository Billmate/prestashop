<?php

require_once BCARDPAY_BASE. '/Billmate.php';

class BillmateCardpayCallbackModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $ajax = true;

	public function postProcess()
	{
		$this->context = Context::getContext();

		$input = Tools::file_get_contents('php://input');
		$_POST = $_GET = $_REQUEST = (array)Tools::jsonDecode($input);
		$ids = explode('-', $_POST['order_id']);
		if (count($ids) < 2) return false;
		//$_POST['order_id'] = $ids[0];
		$cart_id = $ids[0];


		$total_amount = round(($_POST['amount'] / 100), 2);
		if (Tools::getIsset('status') && !empty($_REQUEST['trans_id']) && !empty($_REQUEST['error_message']))
		{
			if (Tools::getValue('status') == 0)
			{
				$lockfile = _PS_CACHE_DIR_.$_POST['order_id'];
				$processing = file_exists($lockfile);
				if ($this->context->cart->orderExists() || $processing)
					die('OK');

				file_put_contents($lockfile, 1);
				$this->context->cart = new Cart($cart_id);
				$customer = new Customer($this->context->cart->id_customer);
				$total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
				$data_return = $this->processReserveInvoice(strtoupper($this->context->country->iso_code), $_REQUEST['order_id']);
				extract($data_return);
				$extra = array('transaction_id' => $invoiceid);
				$this->module->validateOrder((int)$this->context->cart->id, (Configuration::get('BCARDPAY_ORDER_STATUS_SETTINGS')) ? Configuration::get('BCARDPAY_ORDER_STATUS_SETTINGS') : Configuration::get('PS_OS_PAYMENT'), $total, $this->module->displayName, null, $extra, null, false, $customer->secure_key);

				$eid = (int)Configuration::get('BCARDPAY_STORE_ID_SETTINGS');
				$secret = Configuration::get('BCARDPAY_SECRET_SETTINGS');

				$ssl = true;
				$debug = false;

				$k = new BillMate($eid, $secret, $ssl, $debug, Configuration::get('BCARDPAY_MOD'));
				$result = $k->UpdateOrderNo((string)$invoiceid, (string)$this->module->currentOrder);
                if (Configuration::get('BCARDPAY_AUTHMOD') == 'sale')
                    $res = $k->ActivateInvoice((string)$invoiceid);


				//$order = new Order($_POST['order_id']);
				if (!empty($extra))
					Db::getInstance()->update('order_payment', $extra, 'order_reference="'.$this->module->currentOrderReference.'"');

				unlink($lockfile);
			}
		}
		exit('finalize');
	}
	
	public function processReserveInvoice($isocode, $order_id = '')
	{
		$order_id = $order_id == '' ? time(): $order_id;

		//$order = new Order($_POST['order_id']);
		
		//$this->context->cart->id_address_delivery = $order->id_address_delivery;
		//$this->context->cart->id_address_invoice = $order->id_address_invoice;
		//$this->context->cart->id_customer = $order->id_customer;
		$this->context->customer = $customer = new Customer((int)$this->context->cart->id_customer);
		

		$address_delivery = new Address((int)$this->context->cart->id_address_delivery);
		$address_billing = new Address((int)$this->context->cart->id_address_invoice);
		$country = Tools::strtoupper($address_delivery->country);
		$country = new Country((int)$address_delivery->id_country);

		$countryname = BillmateCountry::getContryByNumber(BillmateCountry::fromCode($country->iso_code));
		$countryname = Tools::strtoupper($countryname);

		$eid = (int)Configuration::get('BCARDPAY_STORE_ID_SETTINGS');
		$secret = Configuration::get('BCARDPAY_SECRET_SETTINGS');

		$ssl = true;
		$debug = false;

		$k = new BillMate($eid,$secret,$ssl,$debug,Configuration::get('BCARDPAY_MOD'));

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

		$countryname = BillmateCountry::getContryByNumber( BillmateCountry::fromCode($country->iso_code));
		$countryname = Tools::strtoupper($countryname);
		$country = $countryname == 'SWEDEN' ? 209 : $countryname;
		
		$ship_address = array(
			'email'           => $this->context->customer->email,
			'telno'           => $address_delivery->phone,
			'cellno'          => $address_delivery->phone_mobile,
			'fname'           => $address_delivery->firstname,
			'lname'           => $address_delivery->lastname,
			'company'         => ($address_delivery->company == 'undefined') ? '' : $address_delivery->company,
			'careof'          => '',
			'street'          => $address_delivery->address1,
			'zip'             => $address_delivery->postcode,
			'city'            => $address_delivery->city,
			'country'         => (string)$countryname,
		);

		$country = new Country((int)$address_billing->id_country);

		$countryname = BillmateCountry::getContryByNumber( BillmateCountry::fromCode($country->iso_code));
		$countryname = Tools::strtoupper($countryname);
		$country = $countryname == 'SWEDEN' ? 209 : $countryname;

		$bill_address = array(
			'email'           => $this->context->customer->email,
			'telno'           => $address_billing->phone,
			'cellno'          => $address_billing->phone_mobile,
			'fname'           => $address_billing->firstname,
			'lname'           => $address_billing->lastname,
			'company'         => ($address_billing->company == 'undefined') ? '' : $address_billing->company,
			'careof'          => '',
			'street'          => $address_billing->address1,
			'house_number'    => '',
			'house_extension' => '',
			'zip'             => $address_billing->postcode,
			'city'            => $address_billing->city,
			'country'         => (string)$countryname,
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
		$products = $this->context->cart->getProducts();
		$cart_details = $this->context->cart->getSummaryDetails(null, true);

		$vatrate =  0;
		$goods_list = array();
		foreach ($products as $product)
		{
			if (!empty($product['price']))
			{
				$taxrate = ($product['price_wt'] == $product['price']) ? 0 : $product['rate'];
				$goods_list[] = array(
					'qty'   => (int)$product['cart_quantity'],
					'goods' => array(
						'artno'    => $product['reference'],
						'title'    => $product['name'],
						'price'    => $product['price'] * 100,
						'vat'      => (float)$taxrate,
						'discount' => 0.0,
						'flags'    => 0,
					)
				);
			}
				$vatrate = $taxrate;
		}
		$carrier = $cart_details['carrier'];
		if (!empty($cart_details['total_discounts']))
		{
			$discountamount = $cart_details['total_discounts'] / (($vatrate + 100) / 100);
			if (!empty($discountamount))
			{
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
		}

		$totals = array('total_shipping','total_handling');
		$label =  array();
		//array('total_tax' => 'Tax :'. $cart_details['products'][0]['tax_name']);
		foreach ($totals as $total)
		{
			$flag = $total == 'total_handling' ? 16 : ( $total == 'total_shipping' ? 8 : 0);
			if (empty($cart_details[$total]) || $cart_details[$total]<=0) continue;
			if ($total == 'total_shipping' && $cart_details['free_ship'] == 1) continue;
			if (empty($cart_details[$total])) {continue;}
			if ($total == 'total_shipping'){
				$carrier = new Carrier($this->context->cart->id_carrier, $this->context->cart->id_lang);
				$vatrate = $carrier->getTaxesRate(new Address($this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
			}
			$flags = ($vatrate > 0) ? $flag | 32 : $flag;
			$goods_list[] = array(
				'qty'   => 1,
				'goods' => array(
					'artno'    => '',
					'title'    => isset($label[$total])? $label[$total] : ucwords(str_replace('_', ' ', str_replace('total_', '', $total))),
					'price'    => $cart_details[$total] * 100,
					'vat'      => (float)$vatrate,
					'discount' => 0.0,
					'flags'    => $flags,
				)
			);
		}
		$pclass = -1;
		$cutomerId = (int)$this->context->cart->id_customer;
		$cutomerId = $cutomerId >0 ? $cutomerId: time();

		$transaction = array(
			'order1'=>(string)$order_id,
			'comment'=>'',
			'flags'=>0,
			'gender'=>0,
			'order2'=>'',
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

		//$transaction["extraInfo"][0]["status"] = 'Paid';
		
		//if (Configuration::get('BCARDPAY_AUTHMOD') == 'sale') $transaction['extraInfo'][0]['status'] = 'Paid';

		if (empty($bill_address) || empty($ship_address) || empty($goods_list)) return false;
		
		$result1 = $k->AddInvoice('', $bill_address, $ship_address, $goods_list, $transaction);
		if (is_string($result1) || isset($result1['error']) || !is_array($result1))
			throw new Exception(utf8_encode($result1), 122);
		else
			$_SESSION['INVOICE_CREATED_BANK'] = $result1[0];

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