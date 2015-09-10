<?php
	/**
	 * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
	 *
	 * @author    Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 * @license   OpenSource
	 */

	/*
	 * The controller that does the main integration
	 */

	require_once(_PS_MODULE_DIR_.'billmategateway/library/Billmate.php');


	class BillmategatewayBillmateapiModuleFrontController extends ModuleFrontController {

		protected $method;
		public $module;
		/**
		 * The total sum for costs
		 * @var $totals
		 */
		protected $totals = 0;
		/** @var int $paid_amount for use with Billmate Invoice to sett correct amount */
		protected $paid_amount = 0;
		/**
		 * The total tax amount
		 * @var $tax
		 */
		protected $tax = 0;

		/** @var array with the format array('taxrate' => 'totalamount') */
		protected $prepare_discount = array();
		/** @var  pno | The personal number if invoice or Partpay */
		protected $pno;
		protected $billmate;
		protected $coremodule;
		protected $handling_fee = false;
		protected $handling_taxrate = false;


		public function postProcess()
		{
			if (!defined('BILLMATE_LANGUAGE'))
				define('BILLMATE_LANGUAGE', $this->context->language->iso_code);

			if (!defined('BILLMATE_CLIENT'))
				define('BILLMATE_CLIENT', 'PrestaShop:2.0.0');
            if(!defined('BILLMATE_SERVER'))
                define('BILLMATE_SERVER','2.1.7');
			$this->method = Tools::getValue('method');

			$eid    = Configuration::get('BILLMATE_ID');
			$secret = Configuration::get('BILLMATE_SECRET');
			$ssl    = true;
			$debug  = false;
			require_once(_PS_MODULE_DIR_.'billmategateway/methods/'.Tools::ucfirst($this->method).'.php');

			$class        = Tools::ucfirst($this->method);
			$this->module = new $class;
			$this->coremodule = new BillmateGateway();
			$testmode = $this->module->testMode;

			$this->billmate = Common::getBillmate($eid, $secret, $testmode, $ssl, $debug);

			$this->pno = $this->method == 'invoice' || $this->method == 'partpay'
				? ((Tools::getIsset('pno_billmateinvoice'))
					? Tools::getValue('pno_billmateinvoice')
					: (Tools::getIsset('pno_billmatepartpay')
						? Tools::getValue('pno_billmatepartpay')
						: ''))
				: '';
			/**
			 * @var $data PaymentData
			 */
			$data = array();

			switch ($this->method)
			{
				case 'invoice':
				case 'partpay':
					$result = $this->checkAddress();

					if (is_array($result))
						die(Tools::jsonEncode($result));

					$data = $this->prepareInvoice($this->method);
					break;
				case 'bankpay':
				case 'cardpay':
					$data = $this->prepareDirect($this->method);
					break;
			}

			// Populate Data with the Customer Data and Cart stuff
			$data['Customer'] = $this->prepareCustomer();
			$data['Articles'] = $this->prepareArticles();
			$discounts = $this->prepareDiscounts();
			if (count($discounts) > 0)
			{
				foreach ($discounts as $discount)
					array_push($data['Articles'], $discount);
			}


			$data['Cart']     = $this->prepareTotals();

			$result = $this->billmate->addPayment($data);

			$this->sendResponse($result);
		}

		/**
		 * Returns the Customer object
		 * @return array
		 */
		public function prepareCustomer()
		{
			$customer             = array();
			$customer['nr']       = $this->context->cart->id_customer;
			$customer['pno']      = ($this->method == 'invoice' || $this->method == 'partpay') ? $this->pno : '';
			$billing_address       = new Address($this->context->cart->id_address_invoice);
			$shipping_address      = new Address($this->context->cart->id_address_delivery);
			error_log(print_r( $this->context->customer,true));

			$customer['Billing']  = array(
				'firstname' => mb_convert_encoding($billing_address->firstname,'UTF-8','auto'),
				'lastname'  => mb_convert_encoding($billing_address->lastname,'UTF-8','auto'),
				'company'   => mb_convert_encoding($billing_address->company,'UTF-8','auto'),
				'street'    => mb_convert_encoding($billing_address->address1,'UTF-8','auto'),
				'street2'   => '',
				'zip'       => mb_convert_encoding($billing_address->postcode,'UTF-8','auto'),
				'city'      => mb_convert_encoding($billing_address->city,'UTF-8','auto'),
				'country'   => mb_convert_encoding(Country::getIsoById($billing_address->id_country),'UTF-8','auto'),
				'phone'     => mb_convert_encoding($billing_address->phone,'UTF-8','auto'),
				'email'     => mb_convert_encoding($this->context->customer->email,'UTF-8','auto')
			);
			$customer['Shipping'] = array(
				'firstname' => mb_convert_encoding($shipping_address->firstname,'UTF-8','auto'),
				'lastname'  => mb_convert_encoding($shipping_address->lastname,'UTF-8','auto'),
				'company'   => mb_convert_encoding($shipping_address->company,'UTF-8','auto'),
				'street'    => mb_convert_encoding($shipping_address->address1,'UTF-8','auto'),
				'street2'   => '',
				'zip'       => mb_convert_encoding($shipping_address->postcode,'UTF-8','auto'),
				'city'      => mb_convert_encoding($shipping_address->city,'UTF-8','auto'),
				'country'   => mb_convert_encoding(Country::getIsoById($shipping_address->id_country),'UTF-8','auto'),
				'phone'     => mb_convert_encoding($shipping_address->phone,'UTF-8','auto'),
			);

			return $customer;
		}

		/**
		 * Returns the Articles object and sets the totals of the Articles
		 * @return array
		 */
		public function prepareArticles()
		{
			$articles_arr = array();
			$articles    = $this->context->cart->getProducts();
			foreach ($articles as $article)
			{
				$taxrate       = ($article['price_wt'] == $article['price']) ? 0 : $article['rate'];

				$roundedArticle = round($article['price'], 2);
				$articles_arr[] = array(
					'quantity'   => $article['cart_quantity'],
					'title'      => (isset($article['attributes']) && !empty($article['attributes'])) ? $article['name'].  ' - '.$article['attributes'] : $article['name'],
					'artnr'      => $article['reference'],
					'aprice'     => round(($article['price'] / $article['cart_quantity']) * 100,2),
					'taxrate'    => $taxrate,
					'discount'   => 0,
					'withouttax' => $roundedArticle * 100

				);
				if (!isset($this->prepare_discount[$taxrate]))
					$this->prepare_discount[$taxrate] = $roundedArticle * 100;
				else
					$this->prepare_discount[$taxrate] += $roundedArticle * 100;

				$this->totals += $roundedArticle * 100;
				$this->tax += ($roundedArticle * ($taxrate / 100)) * 100;

			}

			return $articles_arr;
		}

		public function prepareDiscounts()
		{
			$details = $this->context->cart->getSummaryDetails(null, true);

			$discounts = array();
			if (!empty($details['total_discounts']))
			{
				foreach ($this->prepare_discount as $key => $value)
				{

					$percent_discount = $value / ($this->totals);

					$discount_amount = round(($percent_discount * $details['total_discounts']) / (1 + ($key / 100)),2);


					$discounts[]    = array(
						'quantity'   => 1,
						'artnr'      => 'discount-'.$key,
						'title'      => sprintf($this->module->l('Discount %s%% VAT'), $key),
						'aprice'     => -($discount_amount * 100),
						'taxrate'    => $key,
						'discount'   => 0,
						'withouttax' => -$discount_amount * 100
					);

					$this->totals -= $discount_amount * 100;
					$this->tax -= $discount_amount * ($key / 100) * 100;

				}

			}
			if (!empty($details['gift_products']))
			{
				foreach ($details['gift_products'] as $gift)
				{
					$discount_amount = 0;
					$taxrate        = 0;
					foreach ($this->context->cart->getProducts() as $product)
					{
						$taxrate        = ($product['price_wt'] == $product['price']) ? 0 : $product['rate'];
						$discount_amount = $product['price'];
					}
					$price          = $gift['price'] / $gift['cart_quantity'];
					$discount_amount = round($discount_amount / $gift['cart_quantity'],2);
					$total          = $discount_amount * $gift['cart_quantity'] * 100;
					$discounts[]    = array(
						'quantity'   => $gift['cart_quantity'],
						'artnr'      => $this->module->l('Discount'),
						'title'      => $gift['name'],
						'aprice'     => $price - round($discount_amount * 100, 0),
						'taxrate'    => $taxrate,
						'discount'   => 0,
						'withouttax' => $total
					);

					$this->totals -= $total;
					$this->tax -= $total * ($taxrate / 100);
				}
			}

			return $discounts;
		}

		/**
		 * Returns the Cart Object with Totals for Handling, Shipping and Total
		 * @return array
		 */
		public function prepareTotals()
		{
			$totals     = array();
			$details    = $this->context->cart->getSummaryDetails(null, true);

			$carrier    = $details['carrier'];
			$order_total = $this->context->cart->getOrderTotal();
			$notfree    = !(isset($details['free_ship']) && $details['free_ship'] == 1);

			if ($carrier->active && $notfree)
			{
				$carrier_obj = new Carrier($this->context->cart->id_carrier, $this->context->cart->id_lang);
				$taxrate    = $carrier_obj->getTaxesRate(new Address($this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));

				$total_shipping_cost  = round($this->context->cart->getTotalShippingCost(null, false),2);
				$totals['Shipping'] = array(
					'withouttax' => $total_shipping_cost * 100,
					'taxrate'    => $taxrate
				);
				$this->totals += $total_shipping_cost * 100;
				$this->tax += ($total_shipping_cost * ($taxrate / 100)) * 100;
			}
			if (Configuration::get('BINVOICE_FEE') > 0 && $this->method == 'invoice')
			{
				$fee           = Configuration::get('BINVOICE_FEE');
				$invoice_fee_tax = Configuration::get('BINVOICE_FEE_TAX');

				$tax                = new Tax($invoice_fee_tax);
				$tax_calculator      = new TaxCalculator(array($tax));
				$tax_rate            = $tax_calculator->getTotalRate();
				$fee = round($fee,2);
				$totals['Handling'] = array(
					'withouttax' => $fee * 100,
					'taxrate'    => $tax_rate
				);
				$this->handling_fee = $fee;
				$this->handling_taxrate = $tax_rate;
				$order_total += $fee * (1 + ($tax_rate / 100));
				$this->totals += $fee * 100;
				$this->tax += (($tax_rate / 100) * $fee) * 100;
			}

			$rounding         = ($order_total * 100) - $this->tax - $this->totals;
			$totals['Total']  = array(
				'withouttax' => $this->totals,
				'tax'        => $this->tax,
				'rounding'   => $rounding,
				'withtax'    => $this->totals + $this->tax + $rounding
			);
			$this->paid_amount = $totals['Total']['withtax'];

			return $totals;
		}

		/**
		 * Check if the address is matched with our Api
		 * @return true|Array
		 */
		public function checkAddress()
		{
			$address = $this->billmate->getAddress(array('pno' => $this->pno));
			if (isset($address['code']))
			{
				$return = array('success' => false, 'content' => utf8_encode($address['message']));
				die(Tools::jsonEncode($return));
			}
			foreach ($address as $key => $value)
				$address[$key] = mb_convert_encoding($value,'UTF-8','auto');

			$billing  = new Address($this->context->cart->id_address_invoice);
			$shipping = new Address($this->context->cart->id_address_delivery);

			$user_ship = $shipping->firstname.' '.$shipping->lastname;
			$user_bill = $billing->firstname.' '.$billing->lastname;

			$first_arr = explode(' ', $shipping->firstname);
			$last_arr  = explode(' ', $shipping->lastname);

			$apifirst = explode(' ', $address['firstname']);
			$apilast  = explode(' ', $address['lastname']);

			$matched_first = array_intersect($first_arr, $apifirst);
			$matched_last  = array_intersect($last_arr, $apilast);

			$api_matched_name = !empty($matched_first) && !empty($matched_last);

			$address_same = Common::matchstr($user_ship, $user_bill) &&
							Common::matchstr($billing->city, $shipping->city) &&
							Common::matchstr($billing->postcode, $shipping->postcode) &&
							Common::matchstr($billing->address1, $shipping->address1);
			if (!(
				$api_matched_name
				&& Common::matchstr($shipping->address1, $address['street'])
				&& Common::matchstr($shipping->postcode, $address['zip'])
				&& Common::matchstr($shipping->city, $address['city'])
				&& Common::matchstr($address['country'], Country::getIsoById($shipping->id_country))
				&& $address_same
			))
			{
				if (Tools::getValue('geturl') == 'yes')
				{
					// The customer clicked yes
					$cart_details = $this->context->cart->getSummaryDetails(null, true);
					$carrier_id   = $this->context->cart->id_carrier;

					$carrier = new Carrier($carrier_id,$this->context->cart->id_lang);

					$customer_addresses = $this->context->customer->getAddresses($this->context->language->id);

					if (count($customer_addresses) == 1)
						$customer_addresses[] = $customer_addresses;

					$matched_address_id = false;
					foreach ($customer_addresses as $customer_address)
					{
						if (isset($customer_address['address1']))
						{

							if (Common::matchstr($customer_address['address1'], $address['street']) &&
							    Common::matchstr($customer_address['postcode'], $address['zip']) &&
							    Common::matchstr($customer_address['city'], $address['city']) &&
							    Common::matchstr(Country::getIsoById($customer_address['id_country']), $address['country']))
								$matched_address_id = $customer_address['id_address'];
						}
						else
						{
							foreach ($customer_address as $c_address)
							{
								if (Common::matchstr($c_address['address1'], $address['street']) &&
								    Common::matchstr($c_address['postcode'], $address['zip']) &&
								    Common::matchstr($c_address['city'], $address['city']) &&
								    Common::matchstr(Country::getIsoById($c_address['id_country']), $address['country'])
								)
									$matched_address_id = $c_address['id_address'];
							}
						}

					}
					if (!$matched_address_id)
					{
						$addressnew              = new Address();
						$addressnew->id_customer = (int)$this->context->customer->id;

						$addressnew->firstname = $address['firstname'];
						$addressnew->lastname  = $address['lastname'];
						$addressnew->company   = isset($address['company']) ? $address['company'] : '';

						$addressnew->phone        = $billing->phone;
						$addressnew->phone_mobile = $billing->phone_mobile;

						$addressnew->address1 = $address['street'];
						$addressnew->postcode = $address['zip'];
						$addressnew->city     = $address['city'];
						$addressnew->country  = $address['country'];
						$addressnew->alias    = 'Bimport-'.date('Y-m-d');
						$addressnew->id_country = Country::getByIso($address['country']);
						$addressnew->save();

						$matched_address_id = $addressnew->id;
					}
					$this->context->cart->updateAddressId($this->context->cart->id_address_delivery, $matched_address_id);

					$this->context->cart->id_address_invoice  = (int)$matched_address_id;
					$this->context->cart->id_address_delivery = (int)$matched_address_id;
					$this->context->cart->update();

					if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1)
					{
						$return = array(
							'success' => true,
							'action'  => array(
								'method'                                 => 'updateCarrierAndGetPayments',
								//updateExtraCarrier
								'gift'                                   => 0,
								'gift_message'                           => '',
								'recyclable'                             => 0,
								'delivery_option['.$matched_address_id.']' => $carrier->id.',',
								'ajax'                                   => true,
								'token'                                  => Tools::getToken(false),
							)
						);
					}
					else
					{
						$return = array(
							'success' => true,
							'action'  => array(
								'method'             => 'updateExtraCarrier',
								'id_delivery_option' => $carrier_id.',',
								'id_address'         => $matched_address_id,
								'allow_refresh'      => 1,
								'ajax'               => true,
								'token'              => Tools::getToken(false),
							)
						);
					}
				}
				else
				{
					$this->context->smarty->assign(array(
						'ps_version' => _PS_VERSION_,
						'method'     => $this->method
					));
					$this->context->smarty->assign('firstname', $address['firstname']);
					$this->context->smarty->assign('lastname', $address['lastname']);
					$this->context->smarty->assign('address', $address['street']);
					$this->context->smarty->assign('zipcode', $address['zip']);
					$this->context->smarty->assign('city', $address['city']);
					$this->context->smarty->assign('country', Country::getNameById($this->context->language->id,Country::getByIso($address['country'])));
					if (Module::isInstalled('onepagecheckout'))
						$previouslink = $this->context->link->getPageLink('order.php',true);
					else
						$previouslink = $this->context->link->getPageLink('order.php', true).'?step=3';
					$this->context->smarty->assign('previousLink', $previouslink);

					$html   = $this->context->smarty->fetch(_PS_MODULE_DIR_.'billmategateway/views/templates/front/wrongaddress.tpl');
					$return = array('success' => false, 'content' => $html, 'popup' => true);
				}

				return $return;
			}
			else
				return true;

		}

		/**
		 * A method for invoicePreparation for Invoice and Partpayment
		 * @return array Billmate Request
		 */
		public function prepareInvoice($method)
		{
			$payment_data                = array();
			$payment_data['PaymentData'] = array(
				'method'        => ($method == 'invoice') ? 1 : 4,
				'paymentplanid' => ($method == 'partpay') ? Tools::getValue('paymentAccount') : '',
				'currency'      => Tools::strtoupper($this->context->currency->iso_code),
				'language'      => Tools::strtolower($this->context->language->iso_code),
				'country'       => Tools::strtoupper($this->context->country->iso_code),
				'orderid'       => Tools::substr($this->context->cart->id.'-'.time(), 0, 10)
			);

			$payment_data['PaymentInfo'] = array(
				'paymentdate' => date('Y-m-d')
			);

			return $payment_data;

		}

		/**
		 * A method for taking care of Direct and Card payments
		 */
		public function prepareDirect($method)
		{
			$payment_data                = array();
			$payment_data['PaymentData'] = array(
				'method'   => ($method == 'cardpay') ? 8 : 16,
				'currency' => Tools::strtoupper($this->context->currency->iso_code),
				'language' => Tools::strtolower($this->context->language->iso_code),
				'country'  => Tools::strtoupper($this->context->country->iso_code),
				'orderid'  => Tools::substr($this->context->cart->id.'-'.time(), 0, 10),
				'autoactivate' => ($method == 'cardpay' && (Configuration::get('BCARDPAY_AUTHORIZATION_METHOD') != 'authorize')) ? 1 : 0
			);
			$payment_data['PaymentInfo'] = array(
				'paymentdate' => date('Y-m-d')
			);

			$payment_data['Card'] = array(
				'promptname'   => ($method == 'cardpay' && Configuration::get('BILLMATE_CARD_PROMPT')) ? 1 : 0,
				'3dsecure'     => ($method == 'cardpay' && Configuration::get('BILLMATE_CARD_3DSECURE')) ? 1 : 0,
				'accepturl'    => $this->context->link->getModuleLink('billmategateway', 'accept', array('method' => $this->method)),
				'cancelurl'    => $this->context->link->getModuleLink('billmategateway', 'cancel', array('method' => $this->method)),
				'callbackurl'  => $this->context->link->getModuleLink('billmategateway', 'callback', array('method' => $this->method)),
				'returnmethod' => (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == "on") ?'POST' : 'GET'
			);

			return $payment_data;
		}

		public function sendResponse($result)
		{
			$return = array();
			switch ($this->method)
			{
				case 'invoice':
				case 'partpay':
					if (!isset($result['code']))
					{
						$status   = ($this->method == 'invoice') ? Configuration::get('BINVOICE_ORDER_STATUS') : Configuration::get('BPARTPAY_ORDER_STATUS');
						$status = ($result['status'] == 'Pending') ? Configuration::get('BILLMATE_PAYMENT_PENDING') : $status;
						$extra    = array('transaction_id' => $result['number']);
						$total    = $this->context->cart->getOrderTotal();
						$customer = new Customer((int)$this->context->cart->id_customer);
						$orderId = 0;
						if ($this->method == 'partpay')
						{
							$this->module->validateOrder((int)$this->context->cart->id,
								$status,
								($this->method == 'invoice') ? $this->paid_amount / 100 : $total,
								$this->module->displayName,
								null, $extra, null, false, $customer->secure_key);
							$orderId = $this->module->currentOrder;
						}
						else {
							$this->module->validateOrder((int)$this->context->cart->id,
								$status,
								($this->method == 'invoice') ? $this->paid_amount / 100 : $total,
								$this->module->displayName,
								null, $extra, null, false, $customer->secure_key);
							$orderId = $this->module->currentOrder;
						}
						$values                = array();
						$values['PaymentData'] = array(
							'number'  => $result['number'],
							'orderid' => (Configuration::get('BILLMATE_SEND_REFERENCE') == 'reference') ? $this->module->currentOrderReference : $this->module->currentOrder
						);

						$this->billmate->updatePayment($values);

						$url                = 'order-confirmation&id_cart='.(int)$this->context->cart->id.
											  '&id_module='.(int)$this->getmoduleId('billmate'.$this->method).
						                      '&id_order='.(int)$orderId.
											  '&key='.$customer->secure_key;
						$return['success']  = true;
						$return['redirect'] = $this->context->link->getPageLink($url);
					}
					else
					{
						Logger::addLog($result['message'], 1, $result['code'], 'Cart', $this->context->cart->id);
						$return = array('success' => false, 'content' => utf8_encode($result['message']));
					}

					break;
				case 'bankpay':
				case 'cardpay':
					if (!isset($result['code']))
						$return = array('success' => true, 'redirect' => $result['url']);
					else
					{
						Logger::addLog($result['message'], 1, $result['code'], 'Cart', $this->context->cart->id);
						$return = array('success' => false, 'content' => utf8_encode($result['message']));
					}


					break;
			}
			die(Tools::JsonEncode($return));
		}

		public function getmoduleId($method)
		{
			$id2name = array();
			$sql = 'SELECT `id_module`, `name` FROM `'._DB_PREFIX_.'module`';
			if ($results = Db::getInstance()->executeS($sql)) {
				foreach ($results as $row) {
					$id2name[$row['name']] = $row['id_module'];
				}
			}
			return $id2name[$method];
		}

	}