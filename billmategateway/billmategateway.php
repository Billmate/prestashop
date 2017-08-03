<?php
	/**
	 * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
	 *
	 * @author    Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 * @license   OpenSource
	 */

	require_once(_PS_MODULE_DIR_.'/billmategateway/library/Common.php');
	require_once(_PS_MODULE_DIR_.'/billmategateway/library/pclasses.php');

	class BillmateGateway extends PaymentModule {

		protected $allowed_currencies;
		protected $postValidations;
		protected $postErrors;

		public function __construct()
		{
			$this->name       = 'billmategateway';
			$this->moduleName = 'billmategateway';
			$this->tab        = 'payments_gateways';
			$this->version    = BILLMATE_PLUGIN_VERSION;
			$this->author     = 'Billmate AB';

			$this->currencies      = true;
			$this->currencies_mode = 'checkbox';

			parent::__construct();
			$this->core              = null;
			$this->billmate          = null;
			$this->country           = null;
			$this->limited_countries = array(
				'se',
				'nl',
				'dk',
				'no',
				'fi',
				'gb',
				'us'
			); //, 'no', 'fi', 'dk', 'de', 'nl'
			$this->verifyEmail       = $this->l('My email %1$s is accurate and can be used for invoicing.').'<a id="terms" style="cursor:pointer!important"> '.$this->l('I confirm the terms for invoice payment').'</a>';
			/* The parent construct is required for translations */
			$this->page                 = basename(__FILE__, '.php');
			$this->displayName          = $this->l('Billmate Payment Gateway');
			$this->description          = $this->l('Accept online payments with Billmate.');
			$this->confirmUninstall     = $this->l(
				'Are you sure you want to delete your settings?'
			);
			$this->billmate_merchant_id = Configuration::get('BILLMATE_ID');
			$this->billmate_secret      = Configuration::get('BILLMATE_SECRET');
			$installedVersion           = Configuration::get('BILLMATE_VERSION');

			// Is the module installed and need to be updated?
			if ($installedVersion && version_compare($installedVersion, $this->version, '<'))
				$this->update();

			$this->context->smarty->assign('base_dir', __PS_BASE_URI__);
		}

        public function dummyTranslations()
        {
            $this->l('Billmate Cardpay');
            $this->l('Billmate Bankpay');
            $this->l('Billmate Invoice');
            $this->l('Billmate Partpay');
			$this->l('Discount %s%% VAT');
			$this->l('Discount');

        }

		public function getContent()
		{
			$html = '';

			if (!empty($_POST) && Tools::getIsset('billmateSubmit'))
			{
				$this->_postValidation();
				if (count($this->postValidations))
					$html .= $this->displayValidations();

				if (count($this->postErrors))
					$html .= $this->displayErrors();

			}

			$html .= $this->displayAdminTemplate();

			return $html;
		}

		public function displayAdminTemplate()
		{
			$tab   = array();
			$tab[] = array(
				'title'    => $this->l('Settings'),
				'content'  => $this->getGeneralSettings(),
				'icon'     => '../modules/'.$this->moduleName.'/views/img/icon-settings.gif',
				'tab'      => 1,
				'selected' => true,

			);
			$i     = 2;
			foreach ($this->getMethodSettings() as $setting)
			{


				$tab[] = array(
					'title'    => $setting['title'],
					'content'  => $setting['content'],
					'icon'     => '../modules/'.$this->moduleName.'/views/img/icon-settings.gif',
					'tab'      => $i++,
					'selected' => false
				);

			}
			$this->smarty->assign('FormCredential', './index.php?tab=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module='.$this->tab.'&module_name='.$this->name);
			$this->smarty->assign('tab', $tab);
			$this->smarty->assign('moduleName', $this->moduleName);
			$this->smarty->assign($this->moduleName.'Logo', '../modules/'.$this->moduleName.'/views/img/logo.png');
			$this->smarty->assign('js', array('../modules/'.$this->moduleName.'/views/js/billmate.js'));

			$this->smarty->assign('stylecss', '../modules/billmategateway/views/css/billmate.css');

			return $this->display(__FILE__, 'admin.tpl');
		}


		/**
		 * The method takes care of Validation and persisting the posted data
		 */
		public function _postValidation()
		{
			// General Settings

			$billmateId     = Tools::getValue('billmateId');
			$billmateSecret = Tools::getValue('billmateSecret');

			$credentialvalidated = false;
			if ($this->validateCredentials($billmateId, $billmateSecret))
			{
				$credentialvalidated = true;
				Configuration::updateValue('BILLMATE_ID', $billmateId);
				Configuration::updateValue('BILLMATE_SECRET', $billmateSecret);
			}
			Configuration::updateValue('BILLMATE_ACTIVATE', Tools::getIsset('activate') ? 1 : 0);
			Configuration::updateValue('BILLMATE_ACTIVATE_STATUS', serialize(Tools::getValue('activateStatuses')));

			Configuration::updateValue('BILLMATE_CANCEL', Tools::getIsset('credit') ? 1 : 0);
			Configuration::updateValue('BILLMATE_CANCEL_STATUS', serialize(Tools::getValue('creditStatuses')));


			Configuration::updateValue('BILLMATE_MESSAGE', Tools::getIsset('message') ? 1 : 0);



			Configuration::updateValue('BILLMATE_SEND_REFERENCE', Tools::getValue('sendOrderReference'));
			Configuration::updateValue('BILLMATE_GETADDRESS', Tools::getIsset('getaddress') ? 1 : 0);
			Configuration::updateValue('BILLMATE_LOGO',Tools::getValue('logo'));

			// Bankpay Settings
			Configuration::updateValue('BBANKPAY_ENABLED', (Tools::getIsset('bankpayActivated')) ? 1 : 0);
			Configuration::updateValue('BBANKPAY_MOD', (Tools::getIsset('bankpayTestmode')) ? 1 : 0);
			//Configuration::updateValue('BBANKPAY_AUTHORIZATION_METHOD', Tools::getValue('bankpayAuthorization'));
			Configuration::updateValue('BBANKPAY_ORDER_STATUS', Tools::getValue('bankpayBillmateOrderStatus'));
			Configuration::updateValue('BBANKPAY_MIN_VALUE', Tools::getValue('bankpayBillmateMinimumValue'));
			Configuration::updateValue('BBANKPAY_MAX_VALUE', Tools::getValue('bankpayBillmateMaximumValue'));
			Configuration::updateValue('BBANKPAY_SORTORDER', Tools::getValue('bankpayBillmateSortOrder'));

			// Cardpay Settings
			Configuration::updateValue('BCARDPAY_ENABLED', (Tools::getIsset('cardpayActivated')) ? 1 : 0);
			Configuration::updateValue('BCARDPAY_MOD', (Tools::getIsset('cardpayTestmode')) ? 1 : 0);
			Configuration::updateValue('BCARDPAY_ORDER_STATUS', Tools::getValue('cardpayBillmateOrderStatus'));
			Configuration::updateValue('BCARDPAY_AUTHORIZATION_METHOD', Tools::getValue('cardpayAuthorization'));
			Configuration::updateValue('BCARDPAY_MIN_VALUE', Tools::getValue('cardpayBillmateMinimumValue'));
			Configuration::updateValue('BCARDPAY_MAX_VALUE', Tools::getValue('cardpayBillmateMaximumValue'));
			Configuration::updateValue('BCARDPAY_SORTORDER', Tools::getValue('cardpayBillmateSortOrder'));

			// Invoice Settings
			Configuration::updateValue('BINVOICE_ENABLED', (Tools::getIsset('invoiceActivated')) ? 1 : 0);
			Configuration::updateValue('BINVOICE_MOD', (Tools::getIsset('invoiceTestmode')) ? 1 : 0);
			Configuration::updateValue('BINVOICE_FEE', Tools::getValue('invoiceFee'));
			Configuration::updateValue('BINVOICE_FEE_TAX', Tools::getValue('invoiceFeeTax'));
			Configuration::updateValue('BINVOICE_ORDER_STATUS', Tools::getValue('invoiceBillmateOrderStatus'));
			Configuration::updateValue('BINVOICE_MIN_VALUE', Tools::getValue('invoiceBillmateMinimumValue'));
			Configuration::updateValue('BINVOICE_MAX_VALUE', Tools::getValue('invoiceBillmateMaximumValue'));
			Configuration::updateValue('BINVOICE_SORTORDER', Tools::getValue('invoiceBillmateSortOrder'));

			// Invoice Service Settings
			Configuration::updateValue('BINVOICESERVICE_ENABLED', (Tools::getIsset('invoiceserviceActivated')) ? 1 : 0);
			Configuration::updateValue('BINVOICESERVICE_MOD', (Tools::getIsset('invoiceserviceTestmode')) ? 1 : 0);
			Configuration::updateValue('BINVOICESERVICE_FEE', Tools::getValue('invoiceserviceFee'));
			Configuration::updateValue('BINVOICESERVICE_FEE_TAX', Tools::getValue('invoiceserviceFeeTax'));
			Configuration::updateValue('BINVOICESERVICE_ORDER_STATUS', Tools::getValue('invoiceserviceBillmateOrderStatus'));
			Configuration::updateValue('BINVOICESERVICE_MIN_VALUE', Tools::getValue('invoiceserviceBillmateMinimumValue'));
			Configuration::updateValue('BINVOICESERVICE_MAX_VALUE', Tools::getValue('invoiceserviceBillmateMaximumValue'));
			Configuration::updateValue('BINVOICESERVICE_SORTORDER', Tools::getValue('invoiceserviceBillmateSortOrder'));
			Configuration::updateValue('BINVOICESERVICE_FALLBACK',Tools::getIsset('fallbackWhenDifferentAddress') ? 1 : 0);

			// partpay Settings
			Configuration::updateValue('BPARTPAY_ENABLED', (Tools::getIsset('partpayActivated')) ? 1 : 0);
			Configuration::updateValue('BPARTPAY_MOD', (Tools::getIsset('partpayTestmode')) ? 1 : 0);
			Configuration::updateValue('BPARTPAY_ORDER_STATUS', Tools::getValue('partpayBillmateOrderStatus'));
			Configuration::updateValue('BPARTPAY_MIN_VALUE', Tools::getValue('partpayBillmateMinimumValue'));
			Configuration::updateValue('BPARTPAY_MAX_VALUE', Tools::getValue('partpayBillmateMaximumValue'));
			Configuration::updateValue('BPARTPAY_SORTORDER', Tools::getValue('partpayBillmateSortOrder'));
			if (Configuration::get('BPARTPAY_ENABLED') == 1 && $credentialvalidated)
			{
				$pclasses  = new pClasses();
				$languages = Language::getLanguages();
				foreach ($languages as $language)
					$pclasses->Save($this->billmate_merchant_id, $this->billmate_secret, 'se', $language['iso_code'], 'SEK');

			}
		}

		public function validateCredentials($eid, $secret)
		{
			if (empty($eid))
			{
				$this->postErrors[] = $this->l('You must insert a Billmate ID');
				return false;
			}

			if (empty($secret))
			{
				$this->postErrors[] = $this->l('You must insert a Billmate Secret');
				return false;
			}


			$billmate            = Common::getBillmate($eid, $secret, false);
			$data                = array();
			$data['PaymentData'] = array(
				'currency' => 'SEK',
				'language' => 'sv',
				'country'  => 'se'
			);
			$result              = $billmate->getPaymentplans($data);

			if (isset($result['code']) && ($result['code'] == '9010' || $result['code'] == '9012' || $result['code'] == '9013'))
			{
				$this->postErrors[] = utf8_encode($result['message']);

				return false;
			}

			return true;
		}

		public function displayValidations()
		{
			return '';
		}

		public function displayErrors()
		{
			$this->smarty->assign('billmateError', $this->postErrors);

			return $this->display(__FILE__, 'error.tpl');
		}

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

		public function install()
		{
			if (!parent::install())
				return false;


			if (!Configuration::get('BILLMATE_PAYMENT_PENDING'))
				Configuration::updateValue('BILLMATE_PAYMENT_PENDING', $this->addState('Billmate : payment pending', '#DDEEFF'));
			// Inactivate status for modules
			Configuration::updateValue('BPARTPAY_ENABLED', 0);
			Configuration::updateValue('BINVOICE_ENABLED', 0);
			Configuration::updateValue('BCARDPAY_ENABLED', 0);
			Configuration::updateValue('BBANKPAY_ENABLED', 0);
			Configuration::updateValue('BINVOICESERVICE_ENABLED',0);

			Configuration::updateValue('BILLMATE_VERSION', $this->version);
			require_once(_PS_MODULE_DIR_.'/billmategateway/setup/InitInstall.php');
			$installer = new InitInstall(Db::getInstance());
			$installer->install();
			$this->update();
			if (!$this->registerHooks())
				return false;

			if (!function_exists('curl_version'))
			{
				$this->_errors[] = $this->l('Sorry, this module requires the cURL PHP Extension (http://www.php.net/curl), which is not enabled on your server. Please ask your hosting provider for assistance.');
				return false;
			}

			return true;
		}

		public function uninstall(){
			if (!parent::uninstall())
				return false;

			$db = Db::getInstance();
			$db->execute('DELETE FROM '._DB_PREFIX_.'module WHERE name = "billmatebankpay";');
			$db->execute('DELETE FROM '._DB_PREFIX_.'module WHERE name = "billmatepartpay";');
			$db->execute('DELETE FROM '._DB_PREFIX_.'module WHERE name = "billmatecardpay";');
			$db->execute('DELETE FROM '._DB_PREFIX_.'module WHERE name = "billmateinvoice";');
			return true;
		}

		/**
		 * Function to update if module is installed.
		 * Caution need to implement SetupFileInterface to make sure the install function is there
		 * */
		public function update()
		{
			$files = new ArrayObject(iterator_to_array(new FilesystemIterator(_PS_MODULE_DIR_.'/billmategateway/setup/updates', FilesystemIterator::SKIP_DOTS)));
			$files->natsort();
			if (count($files) == 0)
			{
				Configuration::updateValue('BILLMATE_VERSION',$this->version);

				return;
			}
			$installedUpdates = Configuration::get('BILLMATE_UPDATES');
			$installed = array();
			foreach ($files as $file)
			{
				$class = $file->getBasename('.php');


				if($installedUpdates){
					$installed = explode(',',$installedUpdates);
					if(in_array($class,$installed))
						continue;
				}
				if ($class == 'index')
					continue;

				include_once($file->getPathname());

				$updater = new $class(Db::getInstance());
				$updater->install();
				$installed[] = $class;


			}

			Configuration::updateValue('BILLMATE_UPDATES',implode(',',$installed));
			Configuration::updateValue('BILLMATE_VERSION', $this->version);

		}

		public function registerHooks()
		{
			$extra = true;
			if(version_compare(_PS_VERSION_,'1.7','>='))
				$extra = $this->registerHook('paymentOptions');
			return $this->registerHook('displayPayment') &&
				   $this->registerHook('payment') &&
				   $this->registerHook('paymentReturn') &&
				   $this->registerHook('orderConfirmation') &&
				   $this->registerHook('actionOrderStatusUpdate') &&
				   $this->registerHook('displayBackOfficeHeader') &&
				   $this->registerHook('displayAdminOrder') &&
				   $this->registerHook('displayPDFInvoice') &&
					$this->registerHook('displayCustomerAccountFormTop') &&
					$this->registerHook('actionOrderSlipAdd') &&
					$this->registerHook('orderSlip') &&
					$this->registerHook('displayProductButtons') &&
					$extra;

		}

		public function hookDisplayProductButtons($params)
		{
			if(!is_object($params['product'])){
				return '';
			}
			$cost = (1+($params['product']->tax_rate/100))*$params['product']->base_price;

			require_once(_PS_MODULE_DIR_.'/billmategateway/methods/Partpay.php');
			$partpay = new BillmateMethodPartpay();
			$plan = $partpay->getCheapestPlan($cost);
			if($plan) {
				$this->smarty->assign('icon',$partpay->icon);
				$this->smarty->assign('plan', $plan);
				return $this->display(__FILE__, 'payfrom.tpl');
			}
			return '';

		}
		public function hookDisplayPdfInvoice($params)
		{
			$order = new Order($params['object']->id_order);
			$result = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'billmate_payment_fees WHERE order_id = "'.$order->id.'"');
			if($result){


				$invoice_fee_tax = $result['tax_rate'] / 100;
				$invoice_fee = $result['invoice_fee'];
				$billmatetax = $result['invoice_fee'] * $invoice_fee_tax;
				$total_fee = $invoice_fee + $billmatetax;

				$this->smarty->assign('invoiceFeeIncl', $total_fee);
				$this->smarty->assign('invoiceFeeTax', $billmatetax);
				$this->smarty->assign('order', $order);

				return $this->display(__FILE__, 'invoicefeepdf.tpl');
			}
		}

		public function hookDisplayCustomerAccountFormTop($params)
		{
			if (Configuration::get('BILLMATE_GETADDRESS'))
			{

				$this->smarty->assign('pno', (isset($this->context->cookie->billmatepno)) ? $this->context->cookie->billmatepno : '');
				return $this->display(__FILE__, 'getaddress.tpl');
			}
			else
				return;
		}
		/**
		 * This hook displays our invoice Fee in Admin Orders below the client information
		 *
		 * @param $hook
		 */
		public function hookDisplayAdminOrder($hook)
		{
			$order_id = 0;
			if (array_key_exists('id_order', $hook))
				$order_id = (int)$hook['id_order'];


			$order = new Order($order_id);

			$result = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'billmate_payment_fees WHERE order_id = "'.$order->id.'"');
			if($result){

				$payments = $order->getOrderPaymentCollection();
				$currency = 0;
				foreach($payments as $payment){
					$currency = $payment->id_currency;
				}
				$invoice_fee_tax = $result['tax_rate'] / 100;
				$invoice_fee = $result['invoice_fee'];
				$billmatetax = $result['invoice_fee'] * $invoice_fee_tax;

				$total_fee = $invoice_fee + $billmatetax;

				$this->smarty->assign('invoiceFeeIncl', $total_fee);
				$this->smarty->assign('invoiceFeeTax', $billmatetax);
				$this->smarty->assign('invoiceFeeCurrency',$currency);
				$this->smarty->assign('order', $order);

				return $this->display(__FILE__, 'invoicefee.tpl');
			} else {
				return;
			}
		}

		public function hookDisplayBackOfficeHeader()
		{
			if (isset($this->context->cookie->error) && Tools::strlen($this->context->cookie->error) > 2)
			{
				if (get_class($this->context->controller) == 'AdminOrdersController')
				{
					$this->context->controller->errors[] = $this->context->cookie->error;
					unset($this->context->cookie->error);
					unset($this->context->cookie->error_orders);
				}
			}
			if (isset($this->context->cookie->diff) && Tools::strlen($this->context->cookie->diff) > 2)
			{
				if (get_class($this->context->controller) == 'AdminOrdersController')
				{
					$this->context->controller->errors[] = $this->context->cookie->diff;
					unset($this->context->cookie->diff);
					unset($this->context->cookie->diff_orders);
				}
			}
			if (isset($this->context->cookie->api_error) && Tools::strlen($this->context->cookie->api_error) > 2)
			{
				if (get_class($this->context->controller) == 'AdminOrdersController')
				{
					$this->context->controller->errors[] = $this->context->cookie->api_error;
					unset($this->context->cookie->api_error);
					unset($this->context->cookie->api_error_orders);
				}
			}
			if (isset($this->context->cookie->information) && Tools::strlen($this->context->cookie->information) > 2)
			{
				if (get_class($this->context->controller) == 'AdminOrdersController')
				{
					$this->context->controller->warnings[] = $this->context->cookie->information;
					unset($this->context->cookie->information);
					unset($this->context->cookie->information_orders);
				}
			}
			if (isset($this->context->cookie->confirmation) && Tools::strlen($this->context->cookie->confirmation) > 2)
			{
				if (get_class($this->context->controller) == 'AdminOrdersController')
				{
					$this->context->controller->confirmations[] = $this->context->cookie->confirmation;
					unset($this->context->cookie->confirmation);
					unset($this->context->cookie->confirmation_orders);
				}
			}
			if (isset($this->context->cookie->credit_confirmation) && Tools::strlen($this->context->cookie->credit_confirmation) > 2)
			{
				if (get_class($this->context->controller) == 'AdminOrdersController')
				{
					$this->context->controller->confirmations[] = $this->context->cookie->credit_confirmation;
					unset($this->context->cookie->credit_confirmation);
					unset($this->context->cookie->credit_confirmation_orders);
				}
			}
			if (isset($this->context->cookie->error_credit) && Tools::strlen($this->context->cookie->error_credit) > 2)
			{
				if (get_class($this->context->controller) == 'AdminOrdersController')
				{
					$this->context->controller->errors[] = $this->context->cookie->error_credit;
					unset($this->context->cookie->error_credit);
					unset($this->context->cookie->credit_orders);
				}
			}
			if (isset($this->context->cookie->error_credit_activation) && Tools::strlen($this->context->cookie->error_credit_activation) > 2)
			{
				if (get_class($this->context->controller) == 'AdminOrdersController')
				{
					$this->context->controller->errors[] = $this->context->cookie->error_credit_activation;
					unset($this->context->cookie->error_credit_activation);
					unset($this->context->cookie->credit_activate_orders);
				}
			}
			/*if (Tools::getValue('controller') == 'AdminModules' && Tools::getValue('configure') == 'billmategateway')
			{
				$html = '';
				$html = '<link href="/modules/billmategateway/views/css/billmate.css" type="text/css" rel="stylesheet"/>';

				return $html;
			}*/


		}

		/**
		 * @param $params Array array('order' => $order, 'productList' => $order_detail_list, 'qtyList' => $full_quantity_list)
		 */
		public function hookActionOrderSlipAdd($params)
		{

			$order = $params['order'];
			$productList = $params['productList'];
			$qtyList = $params['qtyList'];
			$testMode      = (boolean) $this->getMethodInfo($order->module, 'testMode');

			$billmate = Common::getBillmate($this->billmate_merchant_id,$this->billmate_secret,$testMode);
			$payment = OrderPayment::getByOrderId($order->id);
			$paymentFromBillmate = $billmate->getPaymentinfo(array('number' => $payment[0]->transaction_id));
			if($paymentFromBillmate['PaymentData']['status'] != 'Created' && $paymentFromBillmate['PaymentData']['status'] != 'Cancelled') {

				$values['PaymentData']['number'] = $payment[0]->transaction_id;
				$values['PaymentData']['partcredit'] = true;
				$values['Articles'] = array();
				$tax = 0;
				$total = 0;
				foreach ($productList as $key => $product) {

					$orderDetail = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'order_detail` WHERE `id_order_detail` = ' . (int)$key);
					$orderDetailTax = Db::getInstance()->getRow('SELECT id_tax FROM `' . _DB_PREFIX_ . 'order_detail_tax` WHERE `id_order_detail` = ' . (int)$key);

					$taxData = Db::getInstance()->getRow('SELECT rate FROM `' . _DB_PREFIX_ . 'tax` WHERE id_tax = ' . $orderDetailTax['id_tax']);


					$prodTmp = new Product($orderDetail['product_id']);
					$taxRate = $taxData['rate'];
					$calcTax = $taxRate / 100;


					$marginTax = $calcTax / (1 + $calcTax);

					$price = $product['unit_price'] * (1 - $marginTax);
					//$tax = Tax::getProductTaxRate($product->id, $order->id_address_invoice);
					$values['Articles'][] = array(
							'artnr' => (string)$orderDetail['product_reference'],
							'title' => $orderDetail['product_name'],
							'quantity' => $product['quantity'],
							'aprice' => round($price * 100),
							'taxrate' => $taxRate,
							'discount' => 0,
							'withouttax' => round(100 * ($price * $product['quantity']))
					);
					$total += round(($price * $product['quantity']) * 100);
					$tmpTax = round(100 *(($price * $product['quantity']) * $calcTax));

					$tax += round(100 *(($price * $product['quantity']) * $calcTax));
				}


				$values['Cart']['Total'] = array(
						'withouttax' => round($total),
						'tax' => round($tax),
						'rounding' => 0,
						'withtax' => round($total + $tax)
				);
				$result = $billmate->creditPayment($values);
				if (isset($result['code'])) {
					$this->context->cookie->api_error = $result['message'];
					$this->context->cookie->api_error_orders = isset($this->context->cookie->api_error_orders) ? $this->context->cookie->api_error_orders . ', ' . $order->id : $order->id;

				}
			} else {
				$orderDetailObject = new OrderDetail();
				$total = 0;
				$totaltax = 0;
				$billing_address       = new Address($order->id_address_invoice);
				$shipping_address      = new Address($order->id_address_delivery);
				$values['PaymentData'] = array(
					'number' => $payment[0]->transaction_id
				);
				$values['Customer']['nr'] = $order->id_customer;
				$values['Customer']['Billing']  = array(
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
				$values['Customer']['Shipping'] = array(
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
				foreach($orderDetailObject->getList($order->id) as $orderDetail){
					$orderDetailTax = Db::getInstance()->getRow('SELECT id_tax FROM `' . _DB_PREFIX_ . 'order_detail_tax` WHERE `id_order_detail` = ' . (int)$orderDetail['id_order_detail']);

					$tax = Db::getInstance()->getRow('SELECT rate FROM `' . _DB_PREFIX_ . 'tax` WHERE id_tax = ' . $orderDetailTax['id_tax']);


					$prodTmp = new Product($orderDetail['product_id']);
					$taxRate = $tax['rate'];
					$calcTax = $taxRate / 100;

					$marginTax = $calcTax / (1 + $calcTax);

					$price = $orderDetail['unit_price_tax_excl'];
					//$tax = Tax::getProductTaxRate($orderDetail['product_id'], $order->id_address_invoice);
					$quantity = $orderDetail['product_quantity'] - $orderDetail['product_quantity_refunded'];
					$values['Articles'][] = array(
							'artnr' => (string)$orderDetail['product_reference'],
							'title' => $orderDetail['product_name'],
							'quantity' => $quantity,
							'aprice' => round($price * 100),
							'taxrate' => $taxRate,
							'discount' => 0,
							'withouttax' => round(100 * ($price * $quantity))
					);
					$total += round(($price * $quantity) * 100);
					$totaltax += round((100 * ($price * $quantity)) * $calcTax);
				}


					$taxrate    = $order->carrier_tax_rate;

					$total_shipping_cost  = round($order->total_shipping_tax_excl,2);
					$values['Cart']['Shipping'] = array(
							'withouttax' => round($total_shipping_cost * 100),
							'taxrate'    => $taxrate
					);
					$total += round($total_shipping_cost * 100);
					$totaltax += round(($total_shipping_cost * ($taxrate / 100)) * 100);

				if (Configuration::get('BINVOICE_FEE') > 0 && $order->module == 'billmateinvoice')
				{
					$fee           = Configuration::get('BINVOICE_FEE');
					$invoice_fee_tax = Configuration::get('BINVOICE_FEE_TAX');

					$tax                = new Tax($invoice_fee_tax);
					$tax_calculator      = new TaxCalculator(array($tax));
					$tax_rate            = $tax_calculator->getTotalRate();
					$fee = Tools::convertPriceFull($fee,null,$this->context->currency);
					$fee = round($fee,2);
					$values['Cart']['Handling'] = array(
							'withouttax' => $fee * 100,
							'taxrate'    => $tax_rate
					);

					$total += $fee * 100;
					$totaltax += round((($tax_rate / 100) * $fee) * 100);
				}

				$values['Cart']['Total'] = array(
						'withouttax' => round($total),
						'tax' => round($totaltax),
						'rounding' => 0,
						'withtax' => round($total + $totaltax)
				);
				$result = $billmate->updatePayment($values);
				if (isset($result['code'])) {
					$this->context->cookie->api_error = $result['message'];
					$this->context->cookie->api_error_orders = isset($this->context->cookie->api_error_orders) ? $this->context->cookie->api_error_orders . ', ' . $order->id : $order->id;

				}
			}
		}

		public function hookActionOrderStatusUpdate($params)
		{
			$orderStatus = Configuration::get('BILLMATE_ACTIVATE_STATUS');
			$cancelStatus = Configuration::get('BILLMATE_CANCEL_STATUS');
			$activate    = Configuration::get('BILLMATE_ACTIVATE');
			$cancelStatus = unserialize($cancelStatus);
			$cancelStatus = is_array($cancelStatus) ? $cancelStatus : array($cancelStatus);

			if ($activate && $orderStatus)
			{
				$order_id = $params['id_order'];

				$id_status = $params['newOrderStatus']->id;

				$order     = new Order($order_id);

				$payment     = OrderPayment::getByOrderId($order_id);
				$orderStatus = unserialize($orderStatus);
                $orderStatus = is_array($orderStatus) ? $orderStatus : array($orderStatus);
				$modules     = array('billmatecardpay', 'billmatebankpay', 'billmateinvoice', 'billmatepartpay','billmateinvoiceservice');


				if (in_array($order->module, $modules) && in_array($id_status, $orderStatus) && $this->getMethodInfo($order->module, 'authorization_method') != 'sale')
				{

					$testMode      = $this->getMethodInfo($order->module, 'testMode');
					$billmate      = Common::getBillmate($this->billmate_merchant_id, $this->billmate_secret, $testMode);
					$payment_info   = $billmate->getPaymentinfo(array('number' => $payment[0]->transaction_id));
					$payment_status = Tools::strtolower($payment_info['PaymentData']['status']);
					if ($payment_status == 'created')
					{


						$total      = $payment_info['Cart']['Total']['withtax'] / 100;

						$orderTotal = $order->getTotalPaid();
						$diff       = $total - $orderTotal;
						$diff       = abs($diff);
						if ($diff < 1)
						{
							$result = $billmate->activatePayment(array('PaymentData' => array('number' => $payment[0]->transaction_id)));

							if (isset($result['code']))
							{
								$this->context->cookie->error        = (isset($result['message'])) ? utf8_encode(utf8_decode($result['message'])) : utf8_encode($result);
								$this->context->cookie->error_orders = isset($this->context->cookie->error_orders) ? $this->context->cookie->error_orders.', '.$order_id : $order_id;
							} else {
								$this->context->cookie->confirmation = !isset($this->context->cookie->confirmation_orders) ? sprintf($this->l('Order %s has been activated through Billmate.'), $order_id) . ' (<a target="_blank" href="http://online.billmate.se/faktura">' . $this->l('Open Billmate Online') . '</>)' : sprintf($this->l('The following orders has been activated through Billmate: %s'), $this->context->cookie->confirmation_orders . ', ' . $order_id) . ' (<a target="_blank" href="http://online.billmate.se">' . $this->l('Open Billmate Online') . '</a>)';
								$this->context->cookie->confirmation_orders = isset($this->context->cookie->confirmation_orders) ? $this->context->cookie->confirmation_orders . ', ' . $order_id : $order_id;
							}
						}
						elseif (isset($payment_info['code']))
						{
							if ($payment_info['code'] == 5220)
							{
								$mode                             = $testMode ? 'test' : 'live';
								$this->context->cookie->api_error = !isset($this->context->cookie->api_error_orders) ? sprintf($this->l('Order %s failed to activate through Billmate. The order does not exist in Billmate Online. The order exists in (%s) mode however. Try changing the mode in the modules settings.'), $order_id, $mode).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)' : sprintf($this->l('The following orders failed to activate through Billmate: %s. The orders does not exist in Billmate Online. The orders exists in (%s) mode however. Try changing the mode in the modules settings.'), $this->context->cookie->api_error_orders, '. '.$order_id, $mode).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)';
							}
							else
								$this->context->cookie->api_error = $payment_info['message'];


							$this->context->cookie->api_error_orders = isset($this->context->cookie->api_error_orders) ? $this->context->cookie->api_error_orders.', '.$order_id : $order_id;

						}
						else
						{
							$this->context->cookie->diff        = !isset($this->context->cookie->diff_orders) ? sprintf($this->l('Order %s failed to activate through Billmate. The amounts don\'t match: %s, %s. Activate manually in Billmate Online.'), $order_id, $orderTotal, $total).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)' : sprintf($this->l('The following orders failed to activate through Billmate: %s. The amounts don\'t match. Activate manually in Billmate Online.'), $this->context->cookie->diff_orders.', '.$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)';
							$this->context->cookie->diff_orders = isset($this->context->cookie->diff_orders) ? $this->context->cookie->diff_orders.', '.$order_id : $order_id;
						}
					}
					elseif ($payment_status == 'paid' || $payment_status == 'factoring' || $payment_status == 'partpayment' || $payment_status == 'handling')
					{
						$this->context->cookie->information        = !isset($this->context->cookie->information_orders) ? sprintf($this->l('Order %s is already activated through Billmate.'), $order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)' : sprintf($this->l('The following orders has already been activated through Billmate: %s'), $this->context->cookie->information_orders.', '.$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)';
						$this->context->cookie->information_orders = isset($this->context->cookie->information_orders) ? $this->context->cookie->information_orders.', '.$order_id : $order_id;
					}
					else
					{
						$this->context->cookie->error        = !isset($this->context->cookie->error_orders) ? sprintf($this->l('Order %s failed to activate through Billmate.'), $order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)' : sprintf($this->l('The following orders failed to activate through Billmate: %s.'), $this->context->cookie->error_orders.', '.$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)';
						$this->context->cookie->error_orders = isset($this->context->cookie->error_orders) ? $this->context->cookie->error_orders.', '.$order_id : $order_id;
					}
				} elseif(in_array($order->module, $modules) && in_array($id_status, $cancelStatus)){
					$testMode      = $this->getMethodInfo($order->module, 'testMode');
					$billmate      = Common::getBillmate($this->billmate_merchant_id, $this->billmate_secret, $testMode);

					$payment_info   = $billmate->getPaymentinfo(array('number' => $payment[0]->transaction_id));
					$payment_status = Tools::strtolower($payment_info['PaymentData']['status']);

					if($payment_status == 'paid' || $payment_status == 'factoring' || $payment_status == 'partpayment' || $payment_status == 'handling'){
						$creditResult = $billmate->creditPayment(array('PaymentData' => array('number' => $payment[0]->transaction_id,'partcredit' => false)));
						if(isset($creditResult['code']))
						{
							$this->context->cookie->error_credit        = !isset($this->context->cookie->credit_orders) ? sprintf($this->l('Order %s failed to credit through Billmate.'), $order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)' : sprintf($this->l('The following orders failed to credit through Billmate: %s.'), $this->context->cookie->credit_orders.', '.$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)';

							$this->context->cookie->credit_orders = isset($this->context->cookie->credit_orders) ? $this->context->cookie->credit_orders.', '.$order_id : $order_id;
						} else {
							$this->context->cookie->credit_confirmation        = !isset($this->context->cookie->credit_confirmation_orders) ? sprintf($this->l('Order %s has been credited through Billmate.'), $order_id).' (<a target="_blank" href="http://online.billmate.se/faktura">'.$this->l('Open Billmate Online').'</>)' : sprintf($this->l('The following orders has been credited through Billmate: %s'), $this->context->cookie->credit_confirmation_orders.', '.$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)';
							$this->context->cookie->credit_confirmation_orders = isset($this->context->cookie->credit_confirmation_orders) ? $this->context->cookie->credit_confirmation_orders.', '.$order_id : $order_id;
						}
					} else {
						$cancelResult = $billmate->cancelPayment(array('PaymentData' => array('number' => $payment[0]->transaction_id)));

						if(isset($cancelResult['code'])){
							$this->context->cookie->error_credit        = !isset($this->context->cookie->credit_orders) ? sprintf($this->l('Order %s failed to credit through Billmate.'), $order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)' : sprintf($this->l('The following orders failed to credit through Billmate: %s.'), $this->context->cookie->credit_orders.', '.$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)';

							$this->context->cookie->credit_orders = isset($this->context->cookie->credit_orders) ? $this->context->cookie->credit_orders.', '.$order_id : $order_id;

						} else {
							$this->context->cookie->credit_confirmation        = !isset($this->context->cookie->credit_confirmation_orders) ? sprintf($this->l('Order %s has been credited through Billmate.'), $order_id).' (<a target="_blank" href="http://online.billmate.se/faktura">'.$this->l('Open Billmate Online').'</>)' : sprintf($this->l('The following orders has been credited through Billmate: %s'), $this->context->cookie->credit_confirmation_orders.', '.$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)';
							$this->context->cookie->credit_confirmation_orders = isset($this->context->cookie->credit_confirmation_orders) ? $this->context->cookie->credit_confirmation_orders.', '.$order_id : $order_id;
						}
					}
				}

			}
			else
				return;

		}

		public function hookDisplayPayment($params)
		{
			return $this->hookPayment($params);
		}

		public function hookPayment($params)
		{
			$methods = $this->getMethods($params['cart']);

			$template = 'new';
			if (version_compare(_PS_VERSION_, '1.6', '<'))
				$template = 'legacy';
			if (version_compare(_PS_VERSION_,'1.6.1','=>'))
				$template = 'legacy';

			$this->smarty->assign(
				array(
					'var'        => array(
						'path'          => $this->_path,
						'this_path_ssl' => (_PS_VERSION_ >= 1.4 ? Tools::getShopDomainSsl(true, true) : '').__PS_BASE_URI__.'modules/'.$this->moduleName.'/'
					),
					'template' => $template,
					'methods'    => $methods,
					'ps_version' => _PS_VERSION_,
					'eid' => $this->billmate_merchant_id
				)
			);

			return $this->display(__FILE__, 'payment.tpl');

		}

		public function hookPaymentOptions($params)
		{
			$methods = $this->getMethodOptions($params['cart']);
			$this->smarty->assign(
				array(
					'var'        => array(
						'path'          => $this->_path,
						'this_path_ssl' => (_PS_VERSION_ >= 1.4 ? Tools::getShopDomainSsl(true, true) : '').__PS_BASE_URI__.'modules/'.$this->moduleName.'/'
					),
					'template' => 'ps17',
					'methods'    => $methods,
					'ps_version' => _PS_VERSION_,
					'eid' => $this->billmate_merchant_id
				)
			);

			return $methods;
		}

		public function getFileName()
		{
			return __FILE__;
		}

		public function getMethodOptions($cart)
		{
			$data = array();

			$methodFiles = new FilesystemIterator(_PS_MODULE_DIR_.'/billmategateway/methods', FilesystemIterator::SKIP_DOTS);

			foreach ($methodFiles as $file)
			{
				$class = $file->getBasename('.php');
				if ($class == 'index')
					continue;


				include_once($file->getPathname());

				$class = "BillmateMethod".$class;
				$method = new $class();
				$result = $method->getPaymentInfo($cart);

				if (!$result)
					continue;
				$newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
				$newOption->setModuleName($this->name)
					->setCallToActionText($result['name'])
					->setAction($result['controller'])
					->setLogo($this->context->link->getBaseLink().'/modules/'.$result['icon'])
					->setAdditionalInformation($this->fetch('module:billmategateway/views/templates/front/'.$result['type'].'.tpl'));

				
				if ($result['sort_order'])
				{
					if (array_key_exists($result['sort_order'], $data))
						$data[$result['sort_order'] + 1] = $newOption;
					else
						$data[$result['sort_order']] = $newOption;
				}
				else
					$data[] = $newOption;


			}
			ksort($data);
			return $data;
		}

		public function getMethods($cart)
		{
			$data = array();

			$methodFiles = new FilesystemIterator(_PS_MODULE_DIR_.'/billmategateway/methods', FilesystemIterator::SKIP_DOTS);

			foreach ($methodFiles as $file)
			{
				$class = $file->getBasename('.php');
				if ($class == 'index')
					continue;


				include_once($file->getPathname());

                $class = "BillmateMethod".$class;
				$method = new $class();
				$result = $method->getPaymentInfo($cart);

				if (!$result)
					continue;
				if ($result['sort_order'])
				{
					if (array_key_exists($result['sort_order'], $data))
						$data[$result['sort_order'] + 1] = $result;
					else
						$data[$result['sort_order']] = $result;
				}
				else
					$data[] = $result;


			}
			ksort($data);
			return $data;
		}

		public function getMethodInfo($name, $key)
		{
			$methodFiles = new FilesystemIterator(_PS_MODULE_DIR_.'billmategateway/methods', FilesystemIterator::SKIP_DOTS);
			foreach ($methodFiles as $file)
			{
				$class = $file->getBasename('.php');
				if ($class == 'index')
					continue;
				include_once($file->getPathname());

                $class = "BillmateMethod".$class;
				$method = new $class();


				if ($method->name == $name)
				{

					if (property_exists($method, $key))
						return $method->{$key};

				}
			}
		}

		public function getMethodSettings()
		{
			$data = array();

			$methodFiles = new FilesystemIterator(_PS_MODULE_DIR_.'billmategateway/methods', FilesystemIterator::SKIP_DOTS);

			foreach ($methodFiles as $file)
			{
				$class = $file->getBasename('.php');
				if ($class == 'index')
					continue;

				include_once($file->getPathname());

                $class = "BillmateMethod".$class;
				$method = new $class();
				$result = $method->getSettings();
				if (!$result)
					continue;

				$this->smarty->assign(array('settings' => $result, 'moduleName' => $method->displayName));
				$data[$method->name]['content'] = $this->display(__FILE__, 'settings.tpl');
				$data[$method->name]['title']   = $method->displayName;
			}

			return $data;
		}

		public function getGeneralSettings()
		{
			$settings       = array();
			$statuses       = OrderState::getOrderStates((int)$this->context->language->id);
			$statuses_array = array();
			foreach ($statuses as $status)
				$statuses_array[$status['id_order_state']] = $status['name'];

			$settings['billmateid'] = array(
				'name'     => 'billmateId',
				'required' => true,
				'type'     => 'text',
				'label'    => $this->l('Billmate ID'),
				'desc'     => $this->l('The Billmate ID from Billmate Online'),
				'value'    => Configuration::get('BILLMATE_ID'),
			);

			$settings['billmatesecret'] = array(
				'name'     => 'billmateSecret',
				'required' => true,
				'type'     => 'text',
				'label'    => $this->l('Secret'),
				'desc'     => $this->l('The secret key from Billmate Online'),
				'value'    => Configuration::get('BILLMATE_SECRET')
			);

			$reference = array('orderid' => $this->l('Order ID'),'reference' => $this->l('Reference ID'));
			$settings['sendorderreference'] = array(
				'name'     => 'sendOrderReference',
				'required' => true,
				'type'     => 'select',
				'label'    => $this->l('Order id used by Billmate'),
				'desc'     => '',
				'value'    => Configuration::get('BILLMATE_SEND_REFERENCE') ? Configuration::get('BILLMATE_SEND_REFERENCE') : 'orderid',
				'options' => $reference
			);

			$activate_status      = Configuration::get('BILLMATE_ACTIVATE');
			$settings['activate'] = array(
				'name'     => 'activate',
				'required' => true,
				'type'     => 'checkbox',
				'label'    => $this->l('Activate Invoices'),
				'desc'     => $this->l('Activate Invoices with a certain status in Billmate Online'),
				'value'    => $activate_status
			);

			$settings['activateStatuses'] = array(
				'name'     => 'activateStatuses[]',
				'id'       => 'activation_options',
				'required' => true,
				'type'     => 'multiselect',
				'label'    => $this->l('Order statuses for automatic order activation in Billmate Online'),
				'desc'     => '',
				'value'    => (Tools::safeOutput(Configuration::get('BILLMATE_ACTIVATE_STATUS'))) ? unserialize(Configuration::get('BILLMATE_ACTIVATE_STATUS')) : 0,
				'options'  => $statuses_array
			);
			$credit_status      = Configuration::get('BILLMATE_CANCEL');
			$settings['credit'] = array(
					'name'     => 'credit',
					'required' => true,
					'type'     => 'checkbox',
					'label'    => $this->l('Credit Invoices'),
					'desc'     => $this->l('Credit Invoices with a certain status in Billmate Online'),
					'value'    => $credit_status
			);

			$settings['creditStatuses'] = array(
					'name'     => 'creditStatuses[]',
					'id'       => 'credit_options',
					'required' => true,
					'type'     => 'multiselect',
					'label'    => $this->l('Order statuses for automatic order credit in Billmate Online'),
					'desc'     => '',
					'value'    => (Tools::safeOutput(Configuration::get('BILLMATE_CANCEL_STATUS'))) ? unserialize(Configuration::get('BILLMATE_CANCEL_STATUS')) : 0,
					'options'  => $statuses_array
			);
			$settings['getaddress'] = array(
				'name' => 'getaddress',
				'type' => 'checkbox',
				'label' => $this->l('Activate GetAddress'),
				'desc' => $this->l('Activate get address by social security number to speed up the checkout process.'),
				'value' => Configuration::get('BILLMATE_GETADDRESS')
			);
			$message_status      = Configuration::get('BILLMATE_MESSAGE');
			$settings['message'] = array(
				'name'     => 'message',
				'required' => true,
				'type'     => 'checkbox',
				'label'    => $this->l('Activate Messages on the invoice'),
				'desc'     => $this->l('Add prestashop message to the invoice generated by Billmate'),
				'value'    => $message_status
			);
			$this->smarty->assign('activation_status', $activate_status);

			$settings['logo'] = array(
				'name' => 'logo',
				'type' => 'text',
				'label' => $this->l('Logo to be displayed in the invoice'),
				'desc' => $this->l('Enter the name of the logo (shown in Billmate Online). Leave empty if you only have one logo.'),
				'value' => Configuration::get('BILLMATE_LOGO')
			);
			$this->smarty->assign(array('settings' => $settings, 'moduleName' => $this->l('Common Settings')));

			return $this->display(__FILE__, 'settings.tpl');
		}

		public function hookPaymentReturn($params)
		{
			return $this->hookOrderConfirmation($params);
		}

		public function hookOrderConfirmation($params)
		{
			return $this->display(__FILE__, 'orderconfirmation.tpl');
		}
	}