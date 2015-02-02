<?php
/**
 * The file for specified the BillMate Invoice payment module
 *
 * PHP Version 5.3
 *
 * @category  Payment
 * @package   BillMate_Prestashop
 * @author    Gagan Preet <gaganpreet172@gmail.com>
 * @copyright 2013 Eminenece Technology
 *
 * @link      http://integration.billmate.com/
 */

if (!defined('_CAN_LOAD_FILES_'))
	exit;
	
if( !defined('BILLMATE_BASE')){
	define('BILLMATE_BASE', dirname(dirname(__FILE__)).'/billmateinvoice');
}
include_once(_PS_MODULE_DIR_.'/billmateinvoice/commonfunctions.php');

if (!class_exists('Billmate'))
	require(_PS_MODULE_DIR_.'/billmateinvoice/Billmate.php');


require_once BILLMATE_BASE. '/utf8.php';
require dirname(__FILE__).'/lib/pclasses.php';

class BillmatePartpayment extends PaymentModule
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
	private $_postValidations = array();
	public $countries = array(
		'SE' => array('name' =>'SWEDEN', 'code' => 'SE', 'langue' => 'SV', 'currency' => 'SEK', 'id'=> 209),
		'NO' => array('name' =>'NORWAY', 'code' => 'NO', 'langue' => 'NB', 'currency' => 'NOK', 'id'=> 164),
		'DK' => array('name' =>'DENMARK', 'code' => 'DK', 'langue' => 'DA', 'currency' => 'DKK', 'id'=> 59),
		'FI' => array('name' =>'FINLAND', 'code' => 'FI', 'langue' => 'FI', 'currency' => 'EUR', 'id'=> 73),
		//'GB' => array('name' =>'UNITED_KINGDOM', 'code' => 'GB', 'langue' => 'GB', 'currency' => 'GBP'),
		//'US' => array('name' =>'UNITED_STATES', 'code' => 'US', 'langue' => 'US', 'currency' => 'USD'),
	);

	const RESERVED = 1;
	const SHIPPED = 2;
	const CANCEL = 3;

	/**
	 * Constructor for billmatepartpayment
	 */
	public function getCountries()
	{
		return $this->countries;
	}
	public function verifCountryAndCurrency($country, $currency)
	{

		if (!isset($this->countries[$country->iso_code]))
			return false;
		$currency_code = BillmateCurrency::fromCode($currency->iso_code);
		
		if ($currency_code === null)
			return false;
		if ($this->countries[$country->iso_code]['currency'] != $currency_code)
			return false;
		if (Configuration::get('BILLMATE_STORE_ID_'.$this->countries[$country->iso_code]['name']) > 0
			&& Configuration::get('BILLMATE_SECRET_'.$this->countries[$country->iso_code]['name']) != '')
			return true;
		return false;
	}
	public function enable($forceAll = false){
		parent::enable($forceAll);
		Configuration::updateValue('BILLMATE_ACTIVE_PARTPAYMENT', true );
	}	
	public function disable($forceAll = false){
		parent::disable($forceAll);
		Configuration::updateValue('BILLMATE_ACTIVE_PARTPAYMENT', false );
	}	
	public function __construct()
	{
		$this->name = 'billmatepartpayment';
		$this->moduleName='billmatepartpayment';
		$this->tab = 'payments_gateways';
		$this->version = '1.35.2';
		$this->author = 'Billmate AB';

		$this->currencies = true;
		$this->currencies_mode = 'radio';

		parent::__construct();
		$this->core = null;
		$this->billmate = null;
		$this->country = null;
		$this->limited_countries = array('se', 'onl', 'dk', 'no', 'fi','gb','us'); //, 'no', 'fi', 'dk', 'de', 'nl'

		/* The parent construct is required for translations */
		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Billmate Part Payment');
		$this->description = $this->l('Billmate provides a revolutionary payment solution for online merchants.');
		$this->confirmUninstall = $this->l(
			'Are you sure you want to delete your settings?'
		);

        $this->verifyEmail = $this->l('Min e-postadress %1$s är korrekt och kan användas för fakturering.').'<a id="terms terms-delbetalning" style="cursor:pointer!important"> '.$this->l('I confirm the terms for partpayment').'</a>';
		$this->billmate_merchant_id = Configuration::get('BILLMATE_MERCHANT_ID');
		$this->billmate_secret = Configuration::get('BILLMATE_SECRET');
		$this->billmate_countries = unserialize( Configuration::get('BILLMATE_ENABLED_COUNTRIES_LIST'));
		$this->billmate_fee = Configuration::get('BILLMATE_FEE');
		require(_PS_MODULE_DIR_.'billmatepartpayment/backward_compatibility/backward.php');
		$this->context->smarty->assign('base_dir', __PS_BASE_URI__);
	  }



	/**
	 * Get the content to display on the backend page.
	 *
	 * @return string
	 */
	public function getContent()
	{
		$html = '';
		if (!empty($_POST) && Tools::getIsset('submitBillmate'))
		{
			$this->_postValidation();
			if (sizeof($this->_postValidations))
				$html .= $this->_displayValidation();
			if (sizeof($this->_postErrors))
				$html .= $this->_displayErrors();
		}
		$html .= $this->_displayAdminTpl();
		return $html;
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
		$smarty->assign('js', array('../modules/billmateinvoice/js/billmate.js'));
		$smarty->assign($this->moduleName.'Css', '../modules/billmateinvoice/css/billmate.css');

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
		$countryCodes = array();
		$countryNames = array();
		$input_country = array();
		foreach ($statuses as $status)
			$statuses_array[$status['id_order_state']] = $status['name'];
		foreach ($this->countries as $country)
		{
			$countryNames[$country['name']] = array('flag' => '../modules/'.$this->moduleName.'/img/flag_'.$country['name'].'.png', 'country_name' => $country['name']);
			$countryCodes[$country['id']] = $country['name'];

			$input_country[$country['name']]['eid_'.$country['name']] = array(
				'name' => 'billmateStoreId'.$country['name'],
				'required' => true,
				'value' => Tools::safeOutput(Configuration::get('BILLMATE_STORE_ID_'.$country['name'])),
				'type' => 'text',
				'label' => $this->l('E-store ID'),
				'desc' => $this->l(''),
			);
			$input_country[$country['name']]['secret_'.$country['name']] = array(
				'name' => 'billmateSecret'.$country['name'],
				'required' => true,
				'value' => Tools::safeOutput(Configuration::get('BILLMATE_SECRET_'.$country['name'])),
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
				'value'=> (Tools::safeOutput(Configuration::get('BILLMATE_ORDER_STATUS_'.$country['name']))) ? Tools::safeOutput(Configuration::get('BILLMATE_ORDER_STATUS_'.$country['name'])) : Tools::safeOutput(Configuration::get('PS_OS_PAYMENT')),
				'options' => $statuses_array
			);
			$input_country[$country['name']]['minimum_value_'.$country['name']] = array(
				'name' => 'billmateMinimumValue'.$country['name'],
				'required' => false,
				'value' => (float)Configuration::get('BILLMATE_MIN_VALUE_'.$country['name']),
				'type' => 'text',
				'label' => $this->l('Minimum Value ').'('.$currency['sign'].')',
				'desc' => $this->l(''),
			);
			$input_country[$country['name']]['maximum_value_'.$country['name']] = array(
				'name' => 'billmateMaximumValue'.$country['name'],
				'required' => false,
				'value' => Configuration::get('BILLMATE_MAX_VALUE_'.$country['name']) != 0 ? (float)Configuration::get('BILLMATE_MAX_VALUE_'.$country['name']) : 99999,
				'type' => 'text',
				'label' => $this->l('Maximum Value ').'('.$currency['sign'].')',
				'desc' => $this->l(''),
			);

			if (Configuration::get('BILLMATE_STORE_ID_'.$country['name']))
				$activateCountry[] = $country['name'];
		}

		$smarty->assign($this->moduleName.'FormCredential', './index.php?tab=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module='.$this->tab.'&module_name='.$this->name);
		$smarty->assign($this->moduleName.'CredentialTitle', $this->l('Location'));
		$smarty->assign($this->moduleName.'CredentialText', $this->l('In order to use the Billmate module, please select your host country and supply the appropriate credentials.'));
		$smarty->assign($this->moduleName.'CredentialFootText', $this->l('Please note: The selected currency and country must match the customers\' registration').'<br/>'.
			$this->l('E.g. Swedish customer, SEK, Sweden and Swedish.').'<br/>'.
			$this->l('In order for your customers to use Billmate, your customers must be located in the same country in which your e-store is registered.'));

		$smarty->assign(array(
				'billmate_pclass' => Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'billmate_payment_pclasses`'),
				'billmate_mod' => Configuration::get('BILLMATE_MOD'),
				'billmate_active_partpayment' => Configuration::get('BILLMATE_ACTIVE_PARTPAYMENT'),
				'credentialInputVar' => $input_country,
				'countryNames' => $countryNames,
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
			Configuration::updateValue('BILLMATE_MOD', 0);
		else
			Configuration::updateValue('BILLMATE_MOD', 1);


		if (Tools::getIsset('billmate_active_partpayment') && Tools::getValue('billmate_active_partpayment'))
			Configuration::updateValue('BILLMATE_ACTIVE_PARTPAYMENT', true);
		else
			billmate_deleteConfig('BILLMATE_ACTIVE_PARTPAYMENT');
		

		foreach ($this->countries as $country)
		{
			billmate_deleteConfig('BILLMATE_STORE_ID_'.$country['name']);
			billmate_deleteConfig('BILLMATE_SECRET_'.$country['name']);
			//$key = 'BILLMATE_SECRET_'.$country['name'];
			//var_dump(Configuration::hasKey($key, $lang, $id_shop_group, $id_shop))
		}

		$category_id = Configuration::get('BILLMATE_CATEID');

		if(!empty($category_id)){
			$sql = 'SELECT COUNT(*) FROM '._DB_PREFIX_.'category where id_category="'.$category_id.'"';
			if( Db::getInstance()->getValue($sql) <= 0){
				$category_id = '';
			}
		}

		$pclassesObj = new pClasses();
		$pclassesObj->clear();

		foreach ($this->countries as $key => $country)
		{
			if (Tools::getIsset('activate'.$country['name']))
			{
				$storeId = (int)Tools::getValue('billmateStoreId'.$country['name']);
				$secret = Tools::getValue('billmateSecret'.$country['name']);
					
				$mode   = Configuration::get('BILLMATE_MOD');

				$countryString  = $country['code'];
				$language = $country['langue'];
				$currency = $country['currency'];
				try{
					$pclassesObj->Save($storeId, $secret,$countryString, $language, $currency, $mode);
					if($_SERVER['REMOTE_ADDR'] == '122.173.227.3'){
						
					}
				}catch(Exception $ex){
					$this->_postErrors[] = $ex->getMessage().' - '.$country['name'];
				}
				Configuration::updateValue('BILLMATE_STORE_ID_'.$country['name'], $storeId);
				Configuration::updateValue('BILLMATE_SECRET_'.$country['name'], $secret);
				Configuration::updateValue('BILLMATE_ORDER_STATUS_'.$country['name'], (int)(Tools::getValue('billmateOrderStatus'.$country['name'])));
				Configuration::updateValue('BILLMATE_MIN_VALUE_'.$country['name'], (float)Tools::getValue('billmateMinimumValue'.$country['name']));
				Configuration::updateValue('BILLMATE_MAX_VALUE_'.$country['name'], (Tools::getValue('billmateMaximumValue'.$country['name']) != 0 ? (float)Tools::getValue('billmateMaximumValue'.$country['name']) : 99999));
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
	 * Install the Billmate Invoice module
	 *
	 * @return bool
	 */
	public function install()
	{
		include(dirname(__FILE__).'/sql-install.php');
		foreach ($sql as $s)
		  if (!Db::getInstance()->Execute($s))
			return false;

		if (!parent::install() || !$this->registerHook('payment') || !$this->registerHook('adminOrder') || !$this->registerHook('paymentReturn') || !$this->registerHook('orderConfirmation'))
			return false;


		$this->registerHook('displayPayment');
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

		$languages = array(
			'Swedish' => array('iso_code' => 'se', 'language_code' => 'sv', 'date_format_lite' => 'Y-m-d', 'date_format_full' => 'Y-m-d H:i:s' , 'flag' => 'sweden.png'),
			'Online' => array('iso_code' => 'onl', 'language_code' => 'onl', 'date_format_lite' => 'Y-m-d', 'date_format_full' => 'Y-m-d H:i:s', 'flag' => 'sweden.png'),
			'Deutsch' => array('iso_code' => 'de', 'language_code' => 'de', 'date_format_lite' => 'Y-m-d', 'date_format_full' => 'Y-m-d H:i:s' , 'flag' => 'germany.png'),
			'Dutch' => array('iso_code' => 'nl', 'language_code' => 'nl', 'date_format_lite' => 'Y-m-d', 'date_format_full' => 'Y-m-d H:i:s' , 'flag' => 'netherlands.png'),
			'Finnish' => array('iso_code' => 'fi', 'language_code' => 'fi', 'date_format_lite' => 'Y-m-d', 'date_format_full' => 'Y-m-d H:i:s' , 'flag' => 'finland.jpg'),
			'Norwegian' => array('iso_code' => 'no', 'language_code' => 'no', 'date_format_lite' => 'Y-m-d', 'date_format_full' => 'Y-m-d H:i:s' , 'flag' => 'norway.png'),
			'Danish' => array('iso_code' => 'da', 'language_code' => 'da', 'date_format_lite' => 'Y-m-d', 'date_format_full' => 'Y-m-d H:i:s' , 'flag' => 'denmark.png'),
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
		
		foreach ($this->countries as $key => $val)
		{
			$country = new Country(Country::getByIso($key));
			$country->active = true;
			$country->update();
		}
		/* The hook "displayMobileHeader" has been introduced in v1.5.x - Called separately to fail silently if the hook does not exist */
		return true;
	}

	public function hookOrderConfirmation($params){
		global $smarty;

	}

	/**
	 * Hook the payment module to be shown in the payment selection page
	 *
	 * @param array $params The Smarty instance params
	 *
	 * @return string
	 */
	public function hookdisplayPayment($params)
	{
		return $this->hookPayment($params);
	}

	public function hookPayment($params)
	{
		global $smarty, $link;
		if ( !Configuration::get('BILLMATE_ACTIVE_PARTPAYMENT'))
			return false;

		$total = $this->context->cart->getOrderTotal();
		$_SESSION['billmate'] = array();
		$cart = $params['cart'];
		$address = new Address((int)$cart->id_address_delivery);
		$country = new Country((int)$address->id_country);

		$countryname = BillmateCountry::getContryByNumber(BillmateCountry::fromCode($country->iso_code));
		$countryname = Tools::strtoupper($countryname);

		$minVal = Configuration::get('BILLMATE_MIN_VALUE_'.$countryname);
		$maxVal = Configuration::get('BILLMATE_MAX_VALUE_'.$countryname);

		$eid    = Configuration::get('BILLMATE_STORE_ID_'.$countryname);
		$secret = Configuration::get('BILLMATE_SECRET_'.$countryname);
		$mode   = Configuration::get('BILLMATE_MOD');

		$countryString  = $this->countries[$country->iso_code]['code'];
		$language = $this->countries[$country->iso_code]['langue'];
		$currency = $this->countries[$country->iso_code]['currency'];
		
		$billmate = new pClasses($eid, $secret,$countryString, $language, $currency, $mode);
		$pclass = $billmate->getCheapestPClass((float)$this->context->cart->getOrderTotal(), BillmateFlags::CHECKOUT_PAGE);
		if($_SERVER['REMOTE_ADDR'] == '122.173.227.3'){
			;
			// var_dump($total , $minVal , $total , $maxVal);
			//return true;
		}
		if ($pclass && $pclass['minamount'] <= $this->context->cart->getOrderTotal() &&
			($this->context->cart->getOrderTotal() <= $pclass['maxamount'] || $pclass['maxamount'] == 0 ))
		{
			$value = BillmateCalc::calc_monthly_cost((float)$this->context->cart->getOrderTotal(), $pclass, BillmateFlags::CHECKOUT_PAGE);
		} else{
			return false;
		}

		if( version_compare(_PS_VERSION_, '1.5', '>=') ){
			$moduleurl = $link->getModuleLink('billmatepartpayment', 'validation', array(), true);
		}else{
			$moduleurl = __PS_BASE_URI__.'modules/billmatepartpayment/validation.php?type=partpayment';
		}
		
		$smarty->assign('moduleurl', $moduleurl);
		
		$smarty->assign(array(
				'var' => array('path' => $this->_path, 'this_path_ssl' => (_PS_VERSION_ >= 1.4 ? Tools::getShopDomainSsl(true, true) : '' ).__PS_BASE_URI__.'modules/'.$this->moduleName.'/'),
				'iso_code' => Tools::strtolower($country->iso_code),
				'monthly_amount' => (float)$value,
				'invoiceActive' => Configuration::get('BILLMATE_ACTIVE_INVOICE'),
				'accountActive' => Configuration::get('BILLMATE_ACTIVE_PARTPAYMENT'),
				'specialActive' => true));

		if ($total > $minVal && $total < $maxVal)
		{
			if (version_compare(_PS_VERSION_, '1.6', '>='))
				return $this->display(__FILE__, 'tpl/payment.tpl');
			else
				return $this->display(__FILE__, 'tpl/payment-legacy.tpl');
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
		if(version_compare(_PS_VERSION_, '1.6', '<')){
			return $this->display(dirname(__FILE__).'/', 'tpl/order-confirmation.tpl');
		} else {
			return $this->display(__FILE__,'confirmation.tpl');
		}
	}
}
