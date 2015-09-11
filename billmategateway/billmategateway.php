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
			$this->version    = '2.0.0';
			$this->author     = 'Billmate AB';

			$this->currencies      = true;
			$this->currencies_mode = 'checkbox';

			parent::__construct();
			$this->core              = null;
			$this->billmate          = null;
			$this->country           = null;
			$this->limited_countries = array(
				'se',
				'onl',
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
			Configuration::updateValue('BILLMATE_SEND_REFERENCE', Tools::getValue('sendOrderReference'));
			Configuration::updateValue('BILLMATE_GETADDRESS', Tools::getIsset('getaddress') ? 1 : 0);

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
			Configuration::updateValue('BCARDPAY_3DSECURE', (Tools::getIsset('cardpay3dsecure')) ? 1 : 0);
			Configuration::updateValue('BCARDPAY_PROMPT', (Tools::getIsset('cardpayPromptname')) ? 1 : 0);
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

			Configuration::updateValue('BILLMATE_VERSION', $this->version);
			require_once(_PS_MODULE_DIR_.'/billmategateway/setup/InitInstall.php');
			$installer = new InitInstall(Db::getInstance());
			$installer->install();

			if (!$this->registerHooks())
				return false;

			if (!function_exists('curl_version'))
			{
				$this->_errors[] = $this->l('Sorry, this module requires the cURL PHP Extension (http://www.php.net/curl), which is not enabled on your server. Please ask your hosting provider for assistance.');
				return false;
			}

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
			return $this->registerHook('displayPayment') &&
				   $this->registerHook('payment') &&
				   $this->registerHook('paymentReturn') &&
				   $this->registerHook('orderConfirmation') &&
				   $this->registerHook('actionOrderStatusUpdate') &&
				   $this->registerHook('displayBackOfficeHeader') &&
				   $this->registerHook('displayAdminOrder') &&
				   $this->registerHook('displayPDFInvoice') &&
					$this->registerHook('displayCustomerAccountFormTop');
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


				$invoice_fee_tax = $result['tax_rate'] / 100;
				$invoice_fee = $result['invoice_fee'];
				$billmatetax = $result['invoice_fee'] * $invoice_fee_tax;
				$total_fee = $invoice_fee + $billmatetax;

				$this->smarty->assign('invoiceFeeIncl', $total_fee);
				$this->smarty->assign('invoiceFeeTax', $billmatetax);
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
			/*if (Tools::getValue('controller') == 'AdminModules' && Tools::getValue('configure') == 'billmategateway')
			{
				$html = '';
				$html = '<link href="/modules/billmategateway/views/css/billmate.css" type="text/css" rel="stylesheet"/>';

				return $html;
			}*/


		}

		public function hookActionOrderStatusUpdate($params)
		{
			$orderStatus = Configuration::get('BILLMATE_ACTIVATE_STATUS');
			$activate    = Configuration::get('BILLMATE_ACTIVATE');
			if ($activate && $orderStatus)
			{
				$order_id = $params['id_order'];

				$id_status = $params['newOrderStatus']->id;
				$order     = new Order($order_id);

				$payment     = OrderPayment::getByOrderId($order_id);
				$orderStatus = unserialize($orderStatus);
				$modules     = array('billmatecardpay', 'billmatebankpay', 'billmateinvoice', 'billmatepartpay');

				if (in_array($order->module, $modules) && in_array($id_status, $orderStatus) && $this->getMethodInfo($order->module, 'authorization_method') != 'sale')
				{

					$testMode      = $this->getMethodInfo($order->module, 'testMode');
					$billmate      = Common::getBillmate($this->billmate_merchant_id, $this->billmate_secret, $testMode);
					$payment_info   = $billmate->getPaymentinfo(array('number' => $payment[0]->transaction_id));
					$payment_status = Tools::strtolower($payment_info['PaymentData']['status']);
					if ($payment_status == 'created')
					{
                        $invoice_fee = Configuration::get('BINVOICE_FEE');
                        $total_fee = 0;
                        if ($invoice_fee > 0) {


                            $invoice_fee_tax = Configuration::get('BINVOICE_FEE_TAX');

                            $tax = new Tax($invoice_fee_tax);
                            $tax_calculator = new TaxCalculator(array($tax));

                            $tax_amount = $tax_calculator->getTaxesAmount($invoice_fee);

                            $total_fee = $invoice_fee + $tax_amount[1];
                        }

						$total      = $payment_info['Cart']['Total']['withtax'] / 100;
                        // If Billmate invoice add Invoice fee to prestashop total to make it possible to activate invoice orders
						$orderTotal = $order->getTotalPaid() + ($order->module == 'billmateinvoice') ? $total_fee : 0;
						$diff       = $total - $orderTotal;
						$diff       = abs($diff);
						if ($diff < 1)
						{
							$result = $billmate->activatePayment(array('PaymentData' => array('number' => $payment[0]->transaction_id)));

							if (isset($result['code']))
							{
								$this->context->cookie->error        = (isset($result['message'])) ? utf8_encode($result['message']) : utf8_encode($result);
								$this->context->cookie->error_orders = isset($this->context->cookie->error_orders) ? $this->context->cookie->error_orders.', '.$order_id : $order_id;
							}
							$this->context->cookie->confirmation        = !isset($this->context->cookie->confirmation_orders) ? sprintf($this->l('Order %s has been activated through Billmate.'), $order_id).' (<a target="_blank" href="http://online.billmate.se/faktura">'.$this->l('Open Billmate Online').'</>)' : sprintf($this->l('The following orders has been activated through Billmate: %s'), $this->context->cookie->confirmation_orders.', '.$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)';
							$this->context->cookie->confirmation_orders = isset($this->context->cookie->confirmation_orders) ? $this->context->cookie->confirmation_orders.', '.$order_id : $order_id;
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
					elseif ($payment_status == 'paid')
					{
						$this->context->cookie->information        = !isset($this->context->cookie->information_orders) ? sprintf($this->l('Order %s is already activated through Billmate.'), $order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)' : sprintf($this->l('The following orders has already been activated through Billmate: %s'), $this->context->cookie->information_orders.', '.$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)';
						$this->context->cookie->information_orders = isset($this->context->cookie->information_orders) ? $this->context->cookie->information_orders.', '.$order_id : $order_id;
					}
					else
					{
						$this->context->cookie->error        = !isset($this->context->cookie->error_orders) ? sprintf($this->l('Order %s failed to activate through Billmate.'), $order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)' : sprintf($this->l('The following orders failed to activate through Billmate: %s.'), $this->context->cookie->error_orders.', '.$order_id).' (<a target="_blank" href="http://online.billmate.se">'.$this->l('Open Billmate Online').'</a>)';
						$this->context->cookie->error_orders = isset($this->context->cookie->error_orders) ? $this->context->cookie->error_orders.', '.$order_id : $order_id;
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

		public function getFileName()
		{
			return __FILE__;
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
				$method = new $class();
				if ($method->name == $name)
				{
					if (property_exists($class, $key))
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
				'desc'     => $this->l('The Billmate ID from Billmateonline'),
				'value'    => Configuration::get('BILLMATE_ID'),
			);

			$settings['billmatesecret'] = array(
				'name'     => 'billmateSecret',
				'required' => true,
				'type'     => 'text',
				'label'    => $this->l('Secret'),
				'desc'     => $this->l('The secret key from Billmateonline'),
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
				'name'     => 'activateStatuses',
				'id'       => 'activation_options',
				'required' => true,
				'type'     => 'multiselect',
				'label'    => $this->l('Order statuses for automatic order activation in Billmate Online'),
				'desc'     => '',
				'value'    => (Tools::safeOutput(Configuration::get('BILLMATE_ACTIVATE_STATUS'))) ? unserialize(Configuration::get('BILLMATE_ACTIVATE_STATUS')) : 0,
				'options'  => $statuses_array
			);
			$settings['getaddress'] = array(
				'name' => 'getaddress',
				'type' => 'checkbox',
				'label' => $this->l('Activate GetAddress'),
				'desc' => $this->l('Let your customer use getAddress for checkout'),
				'value' => Configuration::get('BILLMATE_GETADDRESS')
			);
			$this->smarty->assign('activation_status', $activate_status);
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