<?php
/**
 * The file for specified the BillMate Bank payment module
 *
 * PHP Version 5.3
 *
 * @category  Payment
 * @package   BillMate_Prestashop
 * @author    Gagan Preet <gaganpreet172@gmail.com>
 */

if (!defined('_CAN_LOAD_FILES_'))
	exit;

define('BBANK_BASE', dirname(dirname(__FILE__)).'/billmateinvoice');
include_once(_PS_MODULE_DIR_.'/billmateinvoice/commonfunctions.php');
require_once(BBANK_BASE.'/Billmate.php');

/**
 * BillmateBank class
 *
 * @category  Payment
 * @package   BillMate_Prestashop
 * @author    Gagan Preet <gaganpreet172@gmail.com>
 * 
 */
define('BANKPAY_TESTURL', 'https://cardpay.billmate.se/pay/test');
define('BANKPAY_LIVEURL', 'https://cardpay.billmate.se/pay');

class BillmateBank extends PaymentModule
{
	private $_html = '';

	private $_postErrors = array();
	public $billmate;
	public $kitt;
	public $core;
	public $settings;
	public $billmate_merchant_id;
	public $billmate_secret;
	public $billmate_pending_status;
	public $billmate_accepted_status;
	public $billmate_countries;
	public $allowed_currencies = array('SEK');

	private $_postValidations = array();
	
	private $countries = array();

	const RESERVED = 1;
	const SHIPPED = 2;
	const CANCEL = 3;
	
	/**
	 * Constructor for BillmateBank
	 */
	public function __construct()
	{
		$this->countries = array(
			'SE' => array('name' => 'SWEDEN', 'code' => BillmateCountry::SE, 'langue' => BillmateLanguage::SV, 'currency' => BillmateCurrency::SEK)
		);
		$this->name = 'billmatebank';
		$this->moduleName = 'billmatebank';
		$this->tab = 'payments_gateways';
		$this->version = '1.35.2';
		$this->author  = 'Billmate AB';

		$this->currencies = true;
		$this->currencies_mode = 'radio';

		parent::__construct();
		require(_PS_MODULE_DIR_.'billmatepartpayment/backward_compatibility/backward.php');
		$this->core = null;
		$this->billmate = null;
		$this->country = null;
		$this->limited_countries = array('se'); //, 'no', 'fi', 'dk', 'de', 'nl'

		/* The parent construct is required for translations */
		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Billmate Bank');
		$this->description = $this->l('Accepts bank payments by Billmate');
		$this->confirmUninstall = $this->l(
			'Are you sure you want to delete your settings?'
		);
		$this->billmate_merchant_id = Configuration::get('BBANK_MERCHANT_ID');
		$this->billmate_secret = Configuration::get('BBANK_SECRET');
		$this->billmate_countries = unserialize( Configuration::get('BILLMATE_ENABLED_COUNTRIES_LIST'));
	}
	
	/**
	 * Get the content to display on the backend page.
	 *
	 * @return string
	 */
	public function getContent()
	{
		$html = '';
		if (version_compare(_PS_VERSION_, '1.5', '>'))
			$this->context->controller->addJQueryPlugin('fancybox');
		else
			$html .= '<script type="text/javascript" src="'.__PS_BASE_URI__.'js/jquery/jquery.fancybox-1.3.4.js"></script><link type="text/css" rel="stylesheet" href="'.__PS_BASE_URI__.'css/jquery.fancybox-1.3.4.css" />';



		if (!empty($_POST) && Tools::getIsset('submitBillmate'))
		{
			$this->_postValidation();
			if (count($this->_postValidations))
				$html .= $this->_displayValidation();
			if (count($this->_postErrors))
				$html .= $this->_displayErrors();
		}
		$html .= $this->_displayAdminTpl();
		return $html;
	}

    public function hookActionOrderStatusUpdate($params){
        $orderStatus = Configuration::get('BBANK_ACTIVATE_ON_STATUS');
        $activated = Configuration::get('BBANK_ACTIVATE');
        if($orderStatus && $orderStatus != 0 && $activated) {
            $order_id = $params['id_order'];

            $id_status = $params['newOrderStatus']->id;
            $order = new Order($order_id);

            $payment = OrderPayment::getByOrderId($order_id);

            if ($order->module == $this->moduleName && Configuration::get('BBANK_AUTHMOD') != 'sale' && $orderStatus == $id_status) {
                $eid = Configuration::get('BBANK_STORE_ID_SWEDEN');
                $secret = Configuration::get('BBANK_SECRET_SWEDEN');
                $testMode = Configuration::get('BBANK_MOD');
                $ssl = true;
                $debug = false;

                $k = new BillMate((int)$eid, $secret, $ssl, $debug, $testMode);
                $invoice = $k->CheckInvoiceStatus((string)$payment[0]->transaction_id);
                if (Tools::strtolower($invoice) == 'created'){
                    $result = $k->ActivateInvoice((string)$payment[0]->transaction_id);
                    if(is_string($result) || !is_array($result) || isset($result['error']))
                        $this->context->controller->errors[] = (isset($result['error'])) ? utf8_encode($result['error']) : utf8_encode($result);
                }
                elseif (Tools::strtolower($invoice) == 'pending'){
                    $this->context->controller->errors[] = $this->l('Couldn`t activate the invoice, the invoice is manually checked for fraud');
                } else {
                    $this->context->controller->errors[] = $this->l('Couldn`t activate the invoice, please check Billmate Online');
                }
            }
        }
        //Logger::AddLog(print_r($params,true));
    }
	public function enable($force_all = false)
	{
		parent::enable($force_all);
		Configuration::updateValue('BBANK_ACTIVE', true );
	}	
	public function disable($force_all = false)
	{
		parent::disable($force_all);
		Configuration::updateValue('BBANK_ACTIVE', false );
	}	
	/**
	 * @brief Method that will displayed all the tabs in the configurations forms
	 *
	 * @return Rendered form
	 */
	private function _displayAdminTpl()
	{
		$smarty = $this->context->smarty;

		$tab = array(
			'credential' => array(
				'title' => $this->l('Settings'),
				'content' => $this->_displayCredentialTpl(),
				'icon' => '../modules/'.$this->moduleName.'/img/icon-settings.gif',
				'tab' => 1,
				'selected' => true, //other has to be false
			),
		);

		$smarty->assign('tab', $tab);
		$smarty->assign('moduleName', $this->moduleName);
		$smarty->assign($this->moduleName.'Logo', '../modules/'.$this->moduleName.'/img/logo.png');
		$smarty->assign('js', array('../modules/'.$this->moduleName.'/js/billmate.js'));
		$smarty->assign($this->moduleName.'css', '../modules/billmatebank/css/billmate.css');

		return $this->display(__FILE__, 'tpl/admin.tpl');
	}
	/**
	 * @brief Credentials Form Method
	 *
	 * @return Rendered form
	 */
	private function _displayCredentialTpl()
	{
		$smarty = $this->context->smarty;
		$activateCountry = array();
		$currency = Currency::getCurrency((int)Configuration::get('PS_CURRENCY_DEFAULT'));
		$statuses = OrderState::getOrderStates((int)$this->context->language->id);
		$statuses_array = array();
		$countryNames = array();
		$countryCodes = array();
		$input_country = array();
		foreach ($statuses as $status)
			$statuses_array[$status['id_order_state']] = $status['name'];
		foreach ($this->countries as $country)
		{
			$countryNames[$country['name']] = array('flag' => '../modules/'.$this->moduleName.'/img/flag_SWEDEN.png', 'country_name' => $country['name']);
			$countryCodes[$country['code']] = $country['name'];
			
			$input_country[$country['name']]['eid_'.$country['name']] = array(
				'name' => 'billmateStoreId'.$country['name'],
				'required' => true,
				'value' => Tools::safeOutput(Configuration::get('BBANK_STORE_ID_'.$country['name'])),
				'type' => 'text',
				'label' => $this->l('E-store ID'),
				'desc' => $this->l(''),
			);
			$input_country[$country['name']]['secret_'.$country['name']] = array(
				'name' => 'billmateSecret'.$country['name'],
				'required' => true,
				'value' => Tools::safeOutput(Configuration::get('BBANK_SECRET_'.$country['name'])),
				'type' => 'text',
				'label' => $this->l('Secret'),
				'desc' => $this->l(''),
			);
			$input_country[$country['name']]['order_status_'.$country['name']] = array(
				'name' => 'billmateOrderStatus'.$country['name'],
				'required' => true,
				'type' => 'select',
				'label' => $this->l('Set Order Status'),
				'desc' => $this->l(''),
				'value'=> (Tools::safeOutput(Configuration::get('BBANK_ORDER_STATUS_'.$country['name']))) ? Tools::safeOutput(Configuration::get('BBANK_ORDER_STATUS_'.$country['name'])) : Tools::safeOutput(Configuration::get('PS_OS_PAYMENT')) ,
				'options' => $statuses_array
			);
			$input_country[$country['name']]['minimum_value_'.$country['name']] = array(
				'name' => 'billmateMinimumValue'.$country['name'],
				'required' => false,
				'value' => (float)Configuration::get('BBANK_MIN_VALUE_'.$country['name']),
				'type' => 'text',
				'label' => $this->l('Minimum Value ').'('.$currency['sign'].')',
				'desc' => $this->l(''),
			);
			$input_country[$country['name']]['maximum_value_'.$country['name']] = array(
				'name' => 'billmateMaximumValue'.$country['name'],
				'required' => false,
				'value' => Configuration::get('BBANK_MAX_VALUE_'.$country['name']) != 0 ? (float)Configuration::get('BBANK_MAX_VALUE_'.$country['name']) : 99999,
				'type' => 'text',
				'label' => $this->l('Maximum Value ').'('.$currency['sign'].')',
				'desc' => $this->l(''),
			);


			if (Configuration::get('BBANK_STORE_ID_'.$country['name']))
				$activateCountry[] = $country['name'];
		}

        $activateStatuses = array();
        $activateStatuses[0] = $this->l('Inactivated');
        $activateStatuses = $activateStatuses + $statuses_array;
        $status_activate = array(
            'name' => 'billmateActivateOnOrderStatus',
            'id' => 'activationSelect',
            'required' => true,
            'type' => 'select_activate',
            'label' => $this->l('Set Order Status for Invoice Activation'),
            'desc' => $this->l(''),
            'value'=> (Tools::safeOutput(Configuration::get('BBANK_ACTIVATE_ON_STATUS'))) ? Tools::safeOutput(Configuration::get('BBANK_ACTIVATE_ON_STATUS')) : 0,
            'options' => $activateStatuses
        );

		$smarty->assign($this->moduleName.'FormCredential',	'./index.php?tab=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module='.$this->tab.'&module_name='.$this->name);
		$smarty->assign($this->moduleName.'CredentialTitle', $this->l('Location'));
		$smarty->assign($this->moduleName.'CredentialText', $this->l('In order to use the Billmate module, please select your host country and supply the appropriate credentials.'));
		$smarty->assign($this->moduleName.'CredentialFootText', $this->l('Please note: The selected currency and country must match the customers\' registration').'<br/>'.
			$this->l('E.g. Swedish customer, SEK, Sweden and Swedish.').'<br/>'.
			$this->l('In order for your customers to use Billmate, your customers must be located in the same country in which your e-store is registered.'));

        if(version_compare(_PS_VERSION_, '1.5', '>='))
            $showActivate = true;
        else
            $showActivate = false;

		$smarty->assign(array(
                'show_activate' => $showActivate,
                'billmate_activation' => Configuration::get('BBANK_ACTIVATE'),
                'status_activate' => $status_activate,
				'billmate_mod' => Configuration::get('BBANK_MOD'),
				'billmate_active_bank' => Configuration::get('BBANK_ACTIVE'),
				'credentialInputVar' => $input_country,
				'countryNames' => $countryNames,
				'billmatebankCredentialTitle' => $this->l('Location'),
                'billmate_authmod' => Configuration::get('BBANK_AUTHMOD'),
				'countryCodes' => $countryCodes,
				'img' => '../modules/'.$this->moduleName.'/img/',
				'activateCountry' => $activateCountry));

		return $this->display(__FILE__, 'tpl/credential.tpl');
	}

	/**
	 * @brief Error display Method
	 *
	 * @return
	 */
	private function _displayErrors()
	{
		$this->context->smarty->assign('billmateError', $this->_postErrors);
		return $this->display(__FILE__, 'tpl/error.tpl');
	}

	/**
	 * @brief Validate Method
	 *
	 * @return update the module depending on billmate webservices
	 */

	private function _postValidation()
	{
		if (Tools::getValue('billmate_mod') == 'live')
			Configuration::updateValue('BBANK_MOD', 0);
		else
			Configuration::updateValue('BBANK_MOD', 1);

        Configuration::updateValue('BBANK_AUTHMOD', Tools::getValue('billmate_authmod'));
		if (Tools::getIsset('billmate_active_bank') && Tools::getIsset('billmate_active_bank'))
			Configuration::updateValue('BBANK_ACTIVE', true);
		else
			billmate_deleteConfig('BBANK_ACTIVE');

        Configuration::updateValue('BBANK_ACTIVATE_ON_STATUS', Tools::getValue('billmateActivateOnOrderStatus'));
        Configuration::updateValue('BBANK_ACTIVATE', Tools::getValue('billmate_activation'));

		foreach ($this->countries as $country)
		{
			billmate_deleteConfig('BBANK_STORE_ID_'.$country['name']);
			billmate_deleteConfig('BBANK_SECRET_'.$country['name']);
		}

		foreach ($this->countries as $key => $country)
		{

			if (Tools::getIsset('activate'.$country['name']))
			{
				$storeId = (int)Tools::getValue('billmateStoreId'.$country['name']);
				$secret = pSQL(Tools::getValue('billmateSecret'.$country['name']));

				Configuration::updateValue('BBANK_STORE_ID_'.$country['name'], $storeId);
				Configuration::updateValue('BBANK_SECRET_'.$country['name'], $secret);
				Configuration::updateValue('BBANK_ORDER_STATUS_'.$country['name'], (int)(Tools::getValue('billmateOrderStatus'.$country['name'])));
				Configuration::updateValue('BBANK_MIN_VALUE_'.$country['name'], (float)Tools::getValue('billmateMinimumValue'.$country['name']));
				Configuration::updateValue('BBANK_MAX_VALUE_'.$country['name'], (Tools::getValue('billmateMaximumValue'.$country['name']) != 0 ? (float)Tools::getValue('billmateMaximumValue'.$country['name']) : 99999));
	
				$this->_postValidations[] = $this->l('Your account has been updated to be used in ').$country['name'];
			}
		} 
	}
	/**
	 * @brief Validation display Method
	 *
	 * @return
	 */
	private function _displayValidation()
	{
		$this->context->smarty->assign('billmateValidation', $this->_postValidations);
		return $this->display(__FILE__, 'tpl/validation.tpl');
	}


	private function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			Configuration::updateValue('BBANK_MERCHANT_ID_', Tools::getValue('billmate_merchant_id'));
			Configuration::updateValue('BBANK_SECRET_', Tools::getValue('billmate_secret'));

		}
		$this->_html .= '<div class="conf confirm"> '.$this->l('Settings updated').'</div>';
	}
	/******************************************************************/
	/** add payment state ***********************************/
	/******************************************************************/
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

	/**
	 * Install the Billmate Bank module
	 *
	 * @return bool
	 */
	public function install()
	{
		if (!parent::install() || !$this->registerHook('payment') || !$this->registerHook('adminOrder') || !$this->registerHook('paymentReturn') || !$this->registerHook('orderConfirmation'))
			return false;


		$this->registerHook('displayPayment');
		$this->registerHook('header');
        $this->registerHook('actionOrderStatusUpdate');

		if (!Configuration::get('BILLMATE_PAYMENT_ACCEPTED'))
			Configuration::updateValue('BILLMATE_PAYMENT_ACCEPTED', $this->addState('Billmate : Payment accepted', '#DDEEFF'));
		if (!Configuration::get('BILLMATE_PAYMENT_PENDING'))
			Configuration::updateValue('BILLMATE_PAYMENT_PENDING', $this->addState('Billmate : payment in pending verification', '#DDEEFF'));

		/*auto install currencies*/
		$currencies = array(
			'Euro' => array('iso_code' => 'EUR', 'iso_code_num' => 978, 'symbole' => '€', 'format' => 2),
			'Danish Krone' => array('iso_code' => 'DKK', 'iso_code_num' => 208, 'symbole' => 'DAN kr.', 'format' => 2),
			'krone' => array('iso_code' => 'NOK', 'iso_code_num' => 578, 'symbole' => 'NOK kr', 'format' => 2),
			'Krona' => array('iso_code' => 'SEK', 'iso_code_num' => 752, 'symbole' => 'SEK kr', 'format' => 2)
		);


		foreach ($currencies as $key => $val)
		{
			if (_PS_VERSION_ >= 1.5)
				$exists = Currency::exists($val['iso_code_num'], $val['iso_code_num']);
			else
				$exists = Currency::exists($val['iso_code_num']);
			if (!$exists)
			{
				$currency = new Currency();
				$currency->name = $key;
				$currency->iso_code = $val['iso_code'];
				$currency->iso_code_num = $val['iso_code_num'];
				$currency->sign = $val['symbole'];
				$currency->conversion_rate = 1;
				$currency->format = $val['format'];
				$currency->decimals = 1;
				$currency->active = true;
				$currency->add();
			}
		}
		
		Currency::refreshCurrencies();
		
		$version = str_replace('.', '', _PS_VERSION_);
		$version = Tools::substr($version, 0, 2);
		
		
		/* The hook "displayMobileHeader" has been introduced in v1.5.x - Called separately to fail silently if the hook does not exist */


		return true;
	}

	public function hookOrderConfirmation($params)
	{
		global $smarty;

	}
	
	public function hookdisplayPayment($params)
	{
		return $this->hookPayment($params);
	}
	/**
	 * Hook the payment module to be shown in the payment selection page
	 *
	 * @param array $params The Smarty instance params
	 *
	 * @return string
	 */
	public function hookPayment($params)
	{
		global $smarty, $link;

		if (!Configuration::get('BBANK_ACTIVE'))
			return false;

		$total = $this->context->cart->getOrderTotal();

		$cart = $params['cart'];
		$address = new Address((int)$cart->id_address_delivery);
		$country = new Country((int)$address->id_country);

		$countryname = BillmateCountry::getContryByNumber(BillmateCountry::fromCode($country->iso_code));
		$countryname = Tools::strtoupper($countryname);

	   $minVal = Configuration::get('BBANK_MIN_VALUE_'.$countryname);
	   $maxVal = Configuration::get('BBANK_MAX_VALUE_'.$countryname);
	   
	   if(version_compare(_PS_VERSION_, '1.5', '>='))
		$moduleurl = $link->getModuleLink('billmatebank', 'validation', array(), true);
	   else
		$moduleurl = __PS_BASE_URI__.'modules/billmatebank/validation.php';

	   
		$smarty->assign('moduleurl', $moduleurl);
		
		if ($total > $minVal && $total < $maxVal)
		{

			$current_currency_code = trim($this->context->currency->iso_code);
			
			if (!in_array($current_currency_code, $this->allowed_currencies))
				return false;

			if (version_compare(_PS_VERSION_, '1.6', '>='))
				return $this->display(__FILE__, 'tpl/billmatebank.tpl');
			else
				return $this->display(__FILE__, 'tpl/billmatebank-legacy.tpl');
		}
		else
			return false;

	}

	/**
	 * Hook the payment module to display its confirmation template upon
	 * a successful purchase
	 *
	 * @param array $params The Smarty instance params
	 *
	 * @return string
	 */
	public function hookPaymentReturn($params)
	{
		global $cart;

		//customers were to reorder an order done with billmate bank.
		$cart->save();
		if (!$this->active)
			return;

		return $this->display(__FILE__, 'confirmation.tpl');
	}
}
