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

if (!defined('_PS_VERSION_'))
	exit;
if( !defined('BILLMATE_BASE')){
	define('BILLMATE_BASE', dirname(dirname(__FILE__)).'/billmateinvoice');
}

if (!class_exists('Billmate'))
	include_once(_PS_MODULE_DIR_.'billmatepartpayment/lib/Billmate.php');
	include_once(_PS_MODULE_DIR_.'billmatepartpayment/class/billmateintegration.php');
	
require_once BILLMATE_BASE. '/utf8.php';

class BillmatePartpayment extends PaymentModule
{
    private $_html = '';

	private $_postErrors = array();
	private $_postValidations = array();
	public $countries = array(
		'SE' => array('name' =>'SWEDEN', 'code' => BillmateCountry::SE, 'langue' => BillmateLanguage::SV, 'currency' => BillmateCurrency::SEK),
		'NO' => array('name' =>'NORWAY', 'code' => 'NO', 'langue' => 'NB', 'currency' => 'NOK'),
		'DK' => array('name' =>'DENMARK', 'code' => 'DK', 'langue' => 'DA', 'currency' => 'DKK'),
		'FI' => array('name' =>'FINLAND', 'code' => 'FI', 'langue' => 'FI', 'currency' => 'EUR'),
	);

	const RESERVED = 1;
	const SHIPPED = 2;
	const CANCEL = 3;

	/******************************************************************/
	/** Construct Method **********************************************/
	/******************************************************************/
	public function __construct()
	{
		$this->name = 'billmatepartpayment';
		$this->moduleName = 'billmatepartpayment';
		$this->tab = 'payments_gateways';
		$this->version = '1.24';
		$this->author = 'eFinance Nordic AB';

		$this->limited_countries =  array('se', 'onl', 'dk', 'no', 'fi'); //, 'no', 'fi', 'dk', 'de', 'nl'

		parent::__construct();

		$this->displayName = $this->l('Billmate Part Payment');
		$this->description = $this->l('Billmate provides a revolutionary payment solution for online merchants.');

		/* Backward compatibility */
		require(_PS_MODULE_DIR_.$this->moduleName.'/backward_compatibility/backward.php');
		$this->context->smarty->assign('base_dir', __PS_BASE_URI__);
	}


	/******************************************************************/
	/** Install / Uninstall Methods ***********************************/
	/******************************************************************/
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
		$version = substr($version, 0, 2);
		
		foreach ($this->countries as $key => $val)
		{
			$country = new Country(Country::getByIso($key));
			$country->active = true;
			$country->update();
		}
		return true;
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
	 * @brief Uninstall function
	 *
	 * @return Success or failure
	 */
	public function uninstall()
	{
		// Uninstall parent and unregister Configuration
		if (!parent::uninstall() || !$this->unregisterHook('payment') || !$this->unregisterHook('adminOrder'))
			return false;
		return true;
	}

	/**
	 * @brief Main Form Method
	 *
	 * @return Rendered form
	 */
	public function getContent()
	{
		$html = '';
		if (version_compare(_PS_VERSION_,'1.5','>'))
			$this->context->controller->addJQueryPlugin('fancybox');
		else
			$html .= '<script type="text/javascript" src="'.__PS_BASE_URI__.'js/jquery/jquery.fancybox-1.3.4.js"></script><link type="text/css" rel="stylesheet" href="'.__PS_BASE_URI__.'css/jquery.fancybox-1.3.4.css" />';
		  	

		if (!empty($_POST) && isset($_POST['submitBillmate']))
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
		$smarty->assign('js', array('../modules/'.$this->moduleName.'/js/billmate.js'));
		$smarty->assign($this->moduleName.'Css', '../modules/'.$this->moduleName.'/css/billmate.css');

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
		foreach ($this->countries as $country)
		{
			$countryNames[$country['name']] = array('flag' => '../modules/'.$this->moduleName.'/img/flag_'.$country['name'].'.png',	'country_name' => $country['name']);
			$countryCodes[$country['code']] = $country['name'];

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
			$input_country[$country['name']]['minimum_value_'.$country['name']] = array(
				'name' => 'billmateMinimumValue'.$country['name'],
				'required' => false,
				'value' => (float)Configuration::get('BILLMATE_MINIMUM_VALUE_'.$country['name']),
				'type' => 'text',
				'label' => $this->l('Minimum Value ').'('.$currency['sign'].')',
				'desc' => $this->l(''),
			);
			$input_country[$country['name']]['maximum_value_'.$country['name']] = array(
				'name' => 'billmateMaximumValue'.$country['name'],
				'required' => false,
				'value' => Configuration::get('BILLMATE_MAXIMUM_VALUE_'.$country['name']) != 0 ? (float)Configuration::get('BILLMATE_MAXIMUM_VALUE_'.$country['name']) : 99999,
				'type' => 'text',
				'label' => $this->l('Maximum Value ').'('.$currency['sign'].')',
				'desc' => $this->l(''),
			);

			if (Configuration::get('BILLMATE_STORE_ID_'.$country['name']))
				$activateCountry[] = $country['name'];
		}

		$smarty->assign($this->moduleName.'FormCredential',	'./index.php?tab=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module='.$this->tab.'&module_name='.$this->name);
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
	 * @brief Validate Method
	 *
	 * @return update the module depending on billmate webservices
	 */

	private function _postValidation()
	{
		$billmate = new Billmate();
		if ($_POST['billmate_mod'] == 'live')
			Configuration::updateValue('BILLMATE_MOD', Billmate::LIVE);
		else
			Configuration::updateValue('BILLMATE_MOD', Billmate::BETA);


		if (isset($_POST['billmate_active_partpayment']) && $_POST['billmate_active_partpayment'])
			Configuration::updateValue('BILLMATE_ACTIVE_PARTPAYMENT', true);
		else
			Configuration::deleteByName('BILLMATE_ACTIVE_PARTPAYMENT');
		

		foreach ($this->countries as $country)
		{
			Db::getInstance()->delete(_DB_PREFIX_.'billmate_payment_pclasses', 'country = "'.(int)$country['code'].'"');
			Configuration::updateValue('BILLMATE_STORE_ID_'.$country['name'], null);
			Configuration::updateValue('BILLMATE_SECRET_'.$country['name'], null);
		}
		foreach ($this->countries as $key => $country)
		{
			if (isset($_POST['activate'.$country['name']]))
			{
				$storeId = (int)Tools::getValue('billmateStoreId'.$country['name']);
				$secret = pSQL(Tools::getValue('billmateSecret'.$country['name']));

				if (($storeId > 0 && $secret == '') || ($storeId <= 0 && $secret != ''))
					$this->_postErrors[] = $this->l('your credentials are incorrect and can\'t be used in ').$country['name'];
				elseif ($storeId >= 0 && $secret != '')
				{
					$error = false;
					try
					{
						$billmate->config(
							$storeId,               			// Merchant ID
							Tools::safeOutput($secret),			// Shared Secret
							$country['code'],					// Country
							$country['langue'],					// Language
							$country['currency'],				// Currency
							Configuration::get('BILLMATE_MOD'),	// Server
							'mysql',//'json'					// PClass Storage
							$this->_getDb()//,  PClass Storage URI path
							//false,            SSL
							//true              Remote logging of response times of xmlrpc calls
						);
						$PClasses = $billmate->fetchPClasses($country['code']);
					}
					catch (Exception $e)
					{
						$error = true;
						$this->_postErrors[] = (int)$e->getCode().': '.Tools::safeOutput($e->getMessage());
					}

					if (!$error)
					{
						Configuration::updateValue('BILLMATE_STORE_ID_'.$country['name'], $storeId);
						Configuration::updateValue('BILLMATE_SECRET_'.$country['name'], $secret);
						Configuration::updateValue('BILLMATE_MINIMUM_VALUE_'.$country['name'], (float)Tools::getValue('billmateMinimumValue'.$country['name']));
						Configuration::updateValue('BILLMATE_MAXIMUM_VALUE_'.$country['name'], ($_POST['billmateMaximumValue'.$country['name']] != 0 ? (float)Tools::getValue('billmateMaximumValue'.$country['name']) : 99999));

						$taxeRules = TaxRulesGroup::getAssociatedTaxRatesByIdCountry(Country::getByIso($key));
						$maxiPrice = 0;
						$idTaxe = 0;
						foreach ($taxeRules as $key => $val)
							if ((int)$val > $maxiPrice)
							{
								$maxiPrice = (int)$val;
								$idTaxe = $key;
							}

						$this->_postValidations[] = $this->l('Your account has been updated to be used in ').$country['name'];
					}
					$error = false;
				}
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
	 * @brief verify if the currency and the country belong together
	 *
	 * @return true or false
	 */
	public function getCountries()
	{
		return $this->countries;
	}

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


	/**
	 * @brief is the order was in pending verification by billmate
	 *
	 *
	 */
	public function orderHasBeenPending($order)
	{
		return sizeof($order->getHistory(
				(int)($order->id_lang),
				Configuration::get('BILLMATE_PAYMENT_PENDING'))
		);
	}

	/**
	 * @brief is the order was accepted by billmate
	 *
	 *
	 */
	public function orderHasBeenAccepted($order)
	{
		return sizeof($order->getHistory(
				(int)($order->id_lang),
				Configuration::get('BILLMATE_PAYMENT_ACCEPTED'))
		);
	}

	public function orderHasBeenDeclined($order)
	{
		return sizeof($order->getHistory(
				(int)($order->id_lang),
				(int)Configuration::get('PS_OS_CANCELED'))
		);
	}

	private function _verifCurrencyLanguage($currency, $language)
	{
		foreach ($this->countries as $key => $val)
			if ($val['langue'] == $language && $val['currency'] == $currency)
				return $key;
		return false;
	}

	public function hookExtraRight(Array $params)
	{
		if (!$this->active)
			return false;

		if (!Configuration::get('BILLMATE_ACTIVE_PARTPAYMENT'))
			return false;

		if ($this->context->language->iso_code == 'no')
			$iso_code = $this->_verifCurrencyLanguage(BillmateCurrency::fromCode($this->context->currency->iso_code), BillmateLanguage::fromCode('nb'));
		else
			$iso_code = $this->_verifCurrencyLanguage(BillmateCurrency::fromCode($this->context->currency->iso_code), BillmateLanguage::fromCode($this->context->language->iso_code));

		$product = new Product((int)Tools::getValue('id_product'));
		if (Validate::isLoadedObject($params['cart']))
		{
			$cart = $params['cart'];
			$address_delivery = new Address($params['cart']->id_address_delivery);
			$country = new Country($address_delivery->id_country);
			$currency = new Currency($params['cart']->id_currency);
			if (!$this->verifCountryAndCurrency($country, $currency))
				return false;
		}
		else if ($iso_code)
		{
			$country = new Country(pSQL(Country::getByIso($iso_code)));
			$currency = new Currency($this->context->currency->id);
		}
		else
			return false;

		if ($currency->iso_code == 'SEK')
			$countryIsoCode = 'SE';
		else if ($currency->iso_code == 'DKK')
			$countryIsoCode = 'DK';
		else if ($currency->iso_code == 'NOK')
			$countryIsoCode = 'NO';
		else if ($currency->iso_code == 'EUR' && $this->context->language->iso_code == 'fi')
			$countryIsoCode = 'FI';
		else if ($currency->iso_code == 'EUR' && $this->context->language->iso_code == 'de')
		{
			if ((isset($cart) && $country->iso_code == 'DE') || ($this->context->country->iso_code == 'DE' && !isset($cart)))
				$countryIsoCode = 'DE';
			else
				return false;
		}
		else if ($currency->iso_code == 'EUR' && $this->context->language->iso_code == 'nl')
		{
			if ((isset($cart) && $country->iso_code == 'NL') || ($this->context->country->iso_code == 'NL' && !isset($cart)))
				$countryIsoCode = 'NL';
			else
				return false;
		}
		else
			return false;

		if (!Configuration::get('BILLMATE_STORE_ID_'.$this->countries[$countryIsoCode]['name']))
			return false;

		$amount = $product->getPrice();

		$smarty = $this->context->smarty;
		$billmate = new Billmate();

		try
		{
			$billmate->config(
				Configuration::get('BILLMATE_STORE_ID_'.$this->countries[$countryIsoCode]['name']),
				Configuration::get('BILLMATE_SECRET_'.$this->countries[$countryIsoCode]['name']),
				$this->countries[$countryIsoCode]['code'],
				$this->countries[$countryIsoCode]['langue'],
				$this->countries[$countryIsoCode]['currency'],
				Configuration::get('BILLMATE_MOD'),
				'mysql', $this->_getDb());
			$pclass = $billmate->getCheapestPClass((float)$amount, BillmateFlags::PRODUCT_PAGE);
			if ($pclass)
				$value = BillmateCalc::calc_monthly_cost((float)$amount, $pclass, BillmateFlags::PRODUCT_PAGE);
			if (!isset($value) || $value == 0)
				return false;
			$pclasses = array_merge($billmate->getPClasses(BillmatePClass::ACCOUNT), $billmate->getPClasses(BillmatePClass::CAMPAIGN));
			$accountPrice = array();

			foreach ($pclasses as $val)
				$accountPrice[$val->getId()] = array('price' => BillmateCalc::calc_monthly_cost((float)$amount, $val, BillmateFlags::PRODUCT_PAGE),
																			 'month' => (int)$val->getMonths(), 'description' => htmlspecialchars_decode(Tools::safeOutput($val->getDescription())));
		}
		catch (Exception $e)
		{
			return false;
		}

		$this->context->smarty->assign(array(
				'minValue' => (float)$value,
				'accountPrices' => $accountPrice,
				'productcss' => './modules/billmatepartpayment/css/billmateproduct.css',
				'country' => $countryIsoCode,
				'linkTermsCond' => 'https://online.billmate.com/account_'.strtolower($countryIsoCode).'.yaws?eid='.(int)Configuration::get('BILLMATE_STORE_ID_'.$this->countries[$countryIsoCode]['name'])
			));
		return $this->display(__FILE__, 'tpl/product.tpl');
	}

	public function hookHeader($params)
	{
		if (!$this->active)
			return false;

		$namePending = Db::getInstance()->getValue('SELECT `name` FROM `'._DB_PREFIX_.'order_state_lang` WHERE `id_order_state` = \''.(int)Configuration::get('BILLMATE_PAYMENT_PENDING').'\' AND id_lang = \''.(int)$this->context->language->id.'\'');

		$this->context->smarty->assign(array(
				'validateText' =>  $this->l('Billmate: Payment accepted'),
				'wrongText' => Tools::safeOutput($namePending),
				'moduleName' => $this->displayName));

		return $this->display(__FILE__, 'tpl/orderDetail.tpl');
	}

	public function hookRightColumn($params)
	{
		if (!$this->active)
			return false;
		if (isset($params['cart']) && isset($params['cart']->id_address_invoice))
		{
			$address_invoice = new Address((int)$params['cart']->id_address_invoice);
			$country = new Country((int)$address_invoice->id_country);
			if (file_exists('./modules/'.$this->moduleName.'/img/billmate_invoice_'.Tools::strtolower($country->iso_code).'.png') && Configuration::get('BILLMATE_ACTIVE_INVOICE'))
				$logo = 'billmate_invoice_'.Tools::strtolower($country->iso_code).'.png';
			else
				$logo = 'logo.png';
			if (file_exists('./modules/'.$this->moduleName.'/img/billmate_account_'.Tools::strtolower($country->iso_code).'.png') && Configuration::get('BILLMATE_ACTIVE_PARTPAYMENT'))
				$this->context->smarty->assign(array('logo_billmate_account' => Tools::safeOutput('billmate_account_'.Tools::strtolower($country->iso_code).'.png')));
		}
		else
			$logo = 'logo.png';

		$this->context->smarty->assign(array('path' => __PS_BASE_URI__.'modules/'.$this->moduleName, 'logo_billmate' => $logo));
		return $this->display(__FILE__, 'tpl/logo.tpl');
	}

	/**
	 * @brief generate the invoice when the order is ready to be shipped.
	 * the merchant can also cancel the order
	 *
	 */
	public function hookadminOrder($params)
	{
		if (!$this->active)
			return false;
		$order = new Order($params['id_order']);

		$billmate = new Billmate();
		$billmateInt = new BillmateIntegration($billmate);
		if ($order->module != $this->moduleName)
			return false;
		$address_invoice = new Address((int)$order->id_address_invoice);
		$country = new Country((int)$address_invoice->id_country);
		$currency = new Currency((int)$order->id_currency);

		$smarty = $this->context->smarty;

		$billmate->config(
			Configuration::get('BILLMATE_STORE_ID_'.$this->countries[$country->iso_code]['name']),
			Configuration::get('BILLMATE_SECRET_'.$this->countries[$country->iso_code]['name']),
			$this->countries[$country->iso_code]['code'],
			$this->countries[$country->iso_code]['langue'],
			$this->countries[$country->iso_code]['currency'],
			Configuration::get('BILLMATE_MOD'),
			'mysql', $this->_getDb());

		$customer = new Customer($order->id_customer);
		$row = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'billmate_rno` WHERE `id_cart` = '.(int)$order->id_cart);

		$this->initReservation(
			$billmate,
			new Cart((int)$order->id_cart),
			$customer,
			htmlentities($row['house_number'], ENT_QUOTES, 'ISO-8859-1'),
			htmlentities($row['house_ext'], ENT_QUOTES, 'ISO-8859-1')
		);

		if ($country->iso_code == 'DE' || $country->iso_code  == 'NL')
			$gender = ($customer->id_gender == 1 ? 1 : 0);
		else
			$gender = null;

		if ($this->orderHasBeenPending($order) && !$this->orderHasBeenAccepted($order) && !$this->orderHasBeenDeclined($order))
		{
			$redirect = true;
			try
			{
				$result = $billmate->checkOrderStatus($row['rno'],0);
				$history = new OrderHistory();
				$history->id_order = (int)$order->id;
				$history->id_employee = (int)$this->context->employee->id;

				if ($result == BillmateFlags::ACCEPTED)
				{
					$history->changeIdOrderState((int)Configuration::get('BILLMATE_PAYMENT_ACCEPTED'), $order->id);
					$message = $this->l('Billmate has changed the status of this order to Billmate: Payment accepted');
				}
				elseif ($result == BillmateFlags::PENDING)
				{
					$type = 'pending';
					$smarty->assign('shipped_state', (int)Configuration::get('PS_OS_SHIPPING'));
					$message = $this->l('Order still in pending verification, please try again later. Every time you open a pending order in Prestashop, a check for the current status will be made.');
					$noHistory = true;
				}
				elseif ($result == BillmateFlags::DENIED)
				{
					$history->changeIdOrderState((int)Configuration::get('PS_OS_CANCELED'), $order->id);
					Db::getInstance()->autoExecute(_DB_PREFIX_.'billmate_rno', array('state' => self::CANCEL), 'UPDATE', '`id_cart` = '.(int)$order->id_cart);
					$type = 'denied';
					$message = $this->l('Billmate has changed the status of this order to Canceled.');
					$result = $billmateInt->cancel($row['rno']);
				}
			}
			catch (Exception $e)
			{
				$smarty->assign('error', (int)$e->getCode().': '.Tools::safeOutput($e->getMessage()));
				$redirect = false;
			}
			if ($redirect)
			{
				if (!isset($noHistory))
					$history->add();
				$current_index = __PS_BASE_URI__.basename(_PS_ADMIN_DIR_).'/index.php'.(($controller = Tools::getValue('controller')) ? '?controller='.$controller : '');
				if ($back = Tools::getValue('back'))
					$current_index .= '&back='.urlencode($back);
				if (!Tools::getValue('message'))
					Tools::redirectAdmin($current_index.'&id_order='.$order->id.'&vieworder&conf=4&token='.Tools::getValue('token').'&message='.$message.(isset($type) ? '&type='.$type : '&wasPending'));
			}
		}

		if ($order->getCurrentState() == Configuration::get('PS_OS_CANCELED') && !$order->hasBeenShipped() && $row['state'] != self::CANCEL)
		{
			try
			{
				$result = $billmateInt->cancel($row['rno']);
				$smarty->assign('message', $this->l('The order has been canceled in Prestashop and the reservation has been canceled at Billmate.'));
				Db::getInstance()->autoExecute(_DB_PREFIX_.'billmate_rno', array('state' => self::CANCEL), 'UPDATE', '`id_cart` = '.(int)$order->id_cart);
			}
			catch (Exception $e)
			{
				$smarty->assign('error', (int)$e->getCode().': '.Tools::safeOutput($e->getMessage()));
			}
		}

		if ($order->hasBeenShipped() && $row['invoice'] == '')
		{
			$pclass = ($row['type'] == 'invoice' ? BillmatePClass::INVOICE : (int)$row['pclass']);
			try
			{
				$billmate->setEstoreInfo((int)$order->id);
				$result = $billmateInt->activate(
					$row['pno'],
					$row['rno'],
					$gender,                // Gender.
					'',                     // OCR number to use if you have reserved one.
					BillmateFlags::NO_FLAG,   // Flags to affect behavior.
					$pclass								  //BillmatePClass::INVOICE
				);
			}
			catch (Exception $e)
			{
				$smarty->assign('error', (int)$e->getCode().': '.Tools::safeOutput($e->getMessage()));
			}

			if (isset($result) && $result[0] == 'ok')
			{
				Db::getInstance()->autoExecute(_DB_PREFIX_.'billmate_rno', array('invoice' => pSQL($result[1]), 'state' => self::SHIPPED), 'UPDATE', '`id_cart` = '.(int)$order->id_cart);
				$smarty->assign('invoiceLink', (substr($result[1], 0, 4) == 'http' ? Tools::safeOutput($result[1]) : 'https://online.billmate.com/invoices/'.Tools::safeOutput($result[1]).'.pdf'));
				if (Configuration::get('BILLMATE_EMAIL'))
					$billmate->emailInvoice(Tools::safeOutput($result[1]));
			}
		}
		elseif ($order->hasBeenShipped())
			$smarty->assign('invoiceLink', 'https://online.billmate.com/invoices/'.Tools::safeOutput($row['invoice']).'.pdf');

		$smarty->assign('version', (_PS_VERSION_ >= 1.5 ? 1 : 0));

		if ($row['state'] == self::CANCEL)
		{
			$smarty->assign('denied', true);
			if (!Tools::getValue('message'))
				$smarty->assign('message', $this->l('The order has been canceled in Prestashop and the reservation has been canceled at Billmate.'));
		}

		if (Tools::getValue('wasPending'))
			$smarty->assign('wasPending', true);

		if (Tools::getValue('message'))
			$smarty->assign('message', Tools::safeOutput(Tools::getValue('message')));

		if (Tools::getValue('type'))
			$smarty->assign(Tools::safeOutput(Tools::getValue('type')), true);

		return $this->display(__FILE__, 'tpl/adminOrder.tpl');
	}

	private function _verifRange($price, $country)
	{
		if ($price >= Configuration::get('BILLMATE_MINIMUM_VALUE_'.pSQL($country)) && $price <= Configuration::get('BILLMATE_MAXIMUM_VALUE_'.pSQL($country)))
			return true;
		return false;
	}

	public function hookPayment($params)
	{
		return $this->hookdisplayPayment($params);
	}

	public function hookdisplayPayment($params)
	{
		$value = 0;
		if ( !Configuration::get('BILLMATE_ACTIVE_PARTPAYMENT'))
			return false;

		$smarty = $this->context->smarty;

		$billmate = new Billmate();
		$address_invoice = new Address((int)$params['cart']->id_address_invoice);
		$country = new Country((int)$address_invoice->id_country);
		$currency = new Currency((int)$params['cart']->id_currency);

		if (!$this->verifCountryAndCurrency($country, $currency))
			return false;
		if (!$this->_verifRange($params['cart']->getOrderTotal(), $this->countries[$country->iso_code]['name']))
			return false;

		try
		{
			$billmate->config(
				Configuration::get('BILLMATE_STORE_ID_'.$this->countries[$country->iso_code]['name']),
				Configuration::get('BILLMATE_SECRET_'.$this->countries[$country->iso_code]['name']),
				$this->countries[$country->iso_code]['code'],
				$this->countries[$country->iso_code]['langue'],
				$this->countries[$country->iso_code]['currency'],
				Configuration::get('BILLMATE_MOD'),
				'mysql', $this->_getDb());
			$pclass = $billmate->getCheapestPClass((float)$this->context->cart->getOrderTotal(), BillmateFlags::CHECKOUT_PAGE);

			if ($pclass && $pclass->getMinAmount() < $this->context->cart->getOrderTotal())
			{
				if ($country->iso_code == 'NL' && $this->context->cart->getOrderTotal() > 250)
					return false;
				else
					$value = BillmateCalc::calc_monthly_cost((float)$this->context->cart->getOrderTotal(), $pclass, BillmateFlags::CHECKOUT_PAGE);
			}
			$pclassSpec = $billmate->getPClasses(BillmatePClass::SPECIAL);
			if (count($pclassSpec) && $pclassSpec[0]->getExpire() > time())
				$smarty->assign('special', $pclassSpec[0]->getDescription());
		}
		catch (Exception $e)
		{
			return false;
		}

		$smarty->assign(array(
				'var' => array('path' => $this->_path, 'this_path_ssl' => (_PS_VERSION_ >= 1.4 ? Tools::getShopDomainSsl(true, true) : '' ).__PS_BASE_URI__.'modules/'.$this->moduleName.'/'),
				'iso_code' => strtolower($country->iso_code),
				'monthly_amount' => (float)$value,
				'invoiceActive' => Configuration::get('BILLMATE_ACTIVE_INVOICE'),
				'accountActive' => Configuration::get('BILLMATE_ACTIVE_PARTPAYMENT'),
				'specialActive' => true));
		return $this->display(__FILE__, 'tpl/payment.tpl');
	}

	public function billmateEncode($str)
	{
		return iconv('UTF-8', 'ISO-8859-1', $str);
	}

	public function initReservation($billmate, $cart, $customer, $house = null, $ext = null)
	{
		$address_invoice = new Address((int)$cart->id_address_invoice);
		$carrier = new Carrier((int)$cart->id_carrier);
		$country = new Country((int)$address_invoice->id_country);
		$id_currency = (int)Validate::isLoadedObject($this->context->currency) ? (int)$this->context->currency->id : (int)Configuration::get('PS_CURRENCY_DEFAULT');
		$vatrate = 0;
		$order_id = Order::getOrderByCartId((int)$cart->id);
		$increament = 100;
		$hasproduct = false;
		if ($order_id)
		{
			$order = new Order((int)$order_id);
			foreach ($order->getProducts() as $article)
			{
			    
				$price_wt = (float)$article['product_price_wt'];
				$price = (float)$article['product_price'];
				if (empty($article['tax_rate']))
					$rate = round((($price_wt / $price) - 1.0) * 100);
				else
					$rate = $article['tax_rate'];
				$billmate->addArticle(
					(int)$article['product_quantity'],
					$this->billmateEncode($article['product_id']),
					$article['product_name'],
					$price_wt * $increament,
					$rate,
					0,
					BillmateFlags::INC_VAT | (substr($article['product_name'], 0, 10) == 'invoiceFee' ? BillmateFlags::IS_HANDLING : 0));
				$vatrate= $rate;
				$hasproduct = true;
			}
		}
		else
		{
			foreach ($cart->getProducts() as $article)
			{
				$price_wt = (float)$article['price_wt'];
				$price = (float)$article['price'];
				if (empty($article['rate']))
					$rate = round((($price_wt / $price) - 1.0) * 100);
				else
					$rate = $article['rate'];
				$billmate->addArticle(
					(int)$article['cart_quantity'],
					$this->billmateEncode((int)$article['id_product']),
					$article['name'].(isset($article['attributes']) ? $article['attributes'] : ''),
					$price_wt * $increament,
					$rate,
					0,
					BillmateFlags::INC_VAT | (substr($article['name'], 0, 10) == 'invoiceFee' ? BillmateFlags::IS_HANDLING : 0));
				$vatrate= $rate;
				$hasproduct = true;
			}
		}
		if(!$hasproduct ){
			die("No product in cart");
		}
		
		// Add discounts
		if (_PS_VERSION_ >= 1.5)
			$discounts = $cart->getCartRules();
		else
			$discounts = $cart->getDiscounts();

		foreach ($discounts as $discount)
		{
			$rate = 0;
			$incvat = 0;
			// Free shipping has a real value of '!'.
			
			if ($discount['value_real'] !== '!')
			{
				$incvat = $discount['value_real'] * $increament;
				$extvat = $discount['value_tax_exc'] * $increament;
				$rate = round((($incvat / $extvat) - 1.0) * 100);
			}
			$billmate->addArticle(
					1,
					'', // no article number for discounts
				$this->l('Rebate'),
				($incvat * -1),
				$rate,
				0,
				BillmateFlags::INC_VAT
			);
		}


		$carrier = new Carrier((int)$cart->id_carrier);

		if ($carrier->active)
		{
			$taxrate = Tax::getCarrierTaxRate(
				(int)$carrier->id,
				(int)$cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}
			);

// Next we might want to add a shipment fee for the product
			if ($order_id)
			{
				$order = new Order((int)$order_id);
				$shippingPrice = $order->total_shipping_tax_incl;
			}
			else{
				if(version_compare(_PS_VERSION_,'1.5','<'))
				$shippingPrice = $cart->getOrderShippingCost();
				else
				$shippingPrice = $cart->getTotalShippingCost();
			}
			$billmate->addArticle(
				1,
				$this->billmateEncode((int)$cart->id_carrier),
				$this->billmateEncode($carrier->name),
				$shippingPrice * $increament,
				$vatrate,
				0,
				BillmateFlags::INC_VAT | BillmateFlags::IS_SHIPMENT
			);
		}

		if ($cart->gift == 1)
		{
			$rate = 0;
			$wrapping_fees_tax = new Tax(
				(int)Configuration::get('PS_GIFT_WRAPPING_TAX')
			);
			if ($wrapping_fees_tax->rate !== null)
				$rate = $wrapping_fees_tax->rate;

			$billmate->addArticle(
				1,
				'',
				$this->billmateEncode($this->l('Gift wrapping fee')),
				$cart->getOrderTotal(true, Cart::ONLY_WRAPPING),
				$rate,
				0,
				BillmateFlags::INC_VAT
			);
		}

// Create the address object and specify the values.
		$address_delivery = new Address((int)$cart->id_address_delivery);

// Next we tell the Billmate instance to use the address in the next order.
		$address = str_replace($house, '', $address_invoice->address1);
		$address = str_replace($ext, '', $address);

		$address2 = str_replace($house, '', $address_invoice->address2);
		$address2 = str_replace($ext, '', $address2);

		$billmate->setAddress(
			BillmateFlags::IS_BILLING,
			new BillmateAddr(
				$this->billmateEncode($customer->email),
				$this->billmateEncode($address_invoice->phone),
				$this->billmateEncode($address_invoice->phone_mobile),
				$this->billmateEncode($address_invoice->firstname),
				$this->billmateEncode($address_invoice->lastname),
				$this->billmateEncode($address_invoice->company),
				$this->billmateEncode(trim($address).($address2 != '' ? ' '.trim($address2) : '')),
				$this->billmateEncode($address_invoice->postcode),
				$this->billmateEncode($address_invoice->city),
				$this->billmateEncode($this->countries[$country->iso_code]['code']),
				trim($house),
				trim($ext)
			));  // Billing / invoice address

		$address = str_replace($house, '', $address_delivery->address1);
		$address = str_replace($ext, '', $address);

		$address2 = str_replace($house, '', $address_delivery->address2);
		$address2 = str_replace($ext, '', $address2);


		$billmate->setAddress(
			BillmateFlags::IS_SHIPPING,
			new BillmateAddr(
				$this->billmateEncode($customer->email),
				$this->billmateEncode($address_delivery->phone),
				$this->billmateEncode($address_delivery->phone_mobile),
				$this->billmateEncode($address_delivery->firstname),
				$this->billmateEncode($address_delivery->lastname),
				$this->billmateEncode($address_delivery->company),
				$this->billmateEncode(trim($address).($address2 != '' ? ' '.trim($address2) : '')),
				$this->billmateEncode($address_delivery->postcode),
				$this->billmateEncode($address_delivery->city),
				$this->billmateEncode($this->countries[$country->iso_code]['code']),
				trim($house),
				trim($ext)
			));  // Billing / invoice address
	}

	public function isInCart($cart, $id)
	{
		foreach ($cart->getProducts() as $article)
			if ($article['id_product'] == $id)
				return true;
		return false;
	}

	public function setPayment($type)
	{
		
		$address_invoice = new Address((int)$this->context->cart->id_address_invoice);
		$country = new Country((int)$address_invoice->id_country);
		$currency = new Currency((int)$this->context->cart->id_currency);
		if (!$this->verifCountryAndCurrency($country, $currency))
			return false;
		$billmate = new Billmate();
		$billmateInt = new BillmateIntegration($billmate);


		$billmate->config(
			Configuration::get('BILLMATE_STORE_ID_'.$this->countries[$country->iso_code]['name']),
			Configuration::get('BILLMATE_SECRET_'.$this->countries[$country->iso_code]['name']),
			$this->countries[$country->iso_code]['code'],
			$this->countries[$country->iso_code]['langue'],
			$this->countries[$country->iso_code]['currency'],
			Configuration::get('BILLMATE_MOD'),
			'mysql', $this->_getDb());

		$this->initReservation(
			$billmate,
			$this->context->cart,
			$this->context->customer,
			(isset($_POST['billmate_house_number']) ? htmlentities($_POST['billmate_house_number'], ENT_QUOTES, 'ISO-8859-1') : null),
			(isset($_POST['billmate_house_ext']) ? htmlentities($_POST['billmate_house_ext'], ENT_QUOTES, 'ISO-8859-1') : null)
		);

		if (Tools::isSubmit('billmate_pno'))
			$pno = Tools::safeOutput(trim(Tools::getValue('billmate_pno')));
		else
		{
			$day = ($_POST['billmate_pno_day'] < 10 ? '0'.(int)$_POST['billmate_pno_day'] : (int)$_POST['billmate_pno_day']);
			$month = ($_POST['billmate_pno_month'] < 10 ? '0'.(int)$_POST['billmate_pno_month'] : (int)$_POST['billmate_pno_month']);

			$pno = Tools::safeOutput($day.$month.Tools::getValue('billmate_pno_year'));
		}

		$pclass = ($type == 'invoice' ? BillmatePClass::INVOICE : (int)Tools::getValue('paymentAccount'));

		try
		{
			if ($country->iso_code == 'DE' || $country->iso_code  == 'NL')
			{
				if ($this->context->customer->id_gender != 1 && $this->context->customer->id_gender != 2 && $this->context->customer->id_gender != 3)
				{
					$gender = (int)$_POST['billmate_gender'];
					$customer = new Customer($this->context->customer->id);
					$customer->id_gender = (int)$_POST['billmate_gender'];
					$Customer->birthday = (int)$_POST['billmate_pno_year'].'-'.$month.'-'.$day;
					$customer->update();
				}
				else
					$gender = $this->context->customer->id_gender == 1 ? 1 : 0;
			}
			else
				$gender = null;

			$result = $billmateInt->reserve(
				$pno,
				$gender,
		        // Amount. -1 specifies that calculation should calculate the amount
		        // using the goods list
		        -1,
		        BillmateFlags::NO_FLAG, // Flags to affect behavior.
		        // -1 notes that this is an invoice purchase, for part payment purchase
		        // you will have a pclass object on which you use getId().
		        (int)$pclass //BillmatePClass::INVOICE
			);

			if( is_string( $result )){
				return array('success'=> false, 'content'=> $result );
			}
			// Here we get the reservation number or invoice number
			$rno = $result[0];
			Db::getInstance()->autoExecute(
				_DB_PREFIX_.'billmate_rno',
				array(
					'id_cart' => (int)$this->context->cart->id,
					'rno' => pSQL($rno),
					'pno' => pSQL($pno),
					'house_number' => (isset($_POST['billmate_house_number']) ? pSQL($_POST['billmate_house_number']) : null),
					'house_ext' => (isset($_POST['billmate_house_ext']) ? pSQL($_POST['billmate_house_ext']) : null),
					'state' => self::RESERVED,
					'type' => pSQL($type),
					'pclass' => ($type == 'invoice' ? null : (int)Tools::getValue('paymentAccount'))
				),
				'INSERT IGNORE');
			$extra = array('transaction_id'=>$rno );
			
			if ($result[1] == BillmateFlags::PENDING)
				$this->validateOrder((int)$this->context->cart->id,
					Configuration::get('BILLMATE_PAYMENT_PENDING'),
					(float)$this->context->cart->getOrderTotal(),
					$this->displayName,
					null,
					$extra,
					null,
					false,
					$this->context->cart->secure_key);
			else if ($result[1] == BillmateFlags::ACCEPTED)
				$this->validateOrder((int)$this->context->cart->id,
					Configuration::get('BILLMATE_PAYMENT_ACCEPTED'),
					(float)$this->context->cart->getOrderTotal(),
					$this->displayName,
					null,
					$extra,
					null,
					false,
					$this->context->cart->secure_key);
					
			$customer = new Customer((int)$this->context->cart->id_customer);
			$total = $this->context->cart->getOrderTotal(true, Cart::BOTH);

			$this->validateOrder((int)$this->context->cart->id, Configuration::get('PS_OS_PREPARATION'), $total, $this->displayName, null, $extra, null, false, $customer->secure_key);
			$order_id = (int)$this->currentOrder;
			
			$updateResult = $billmateInt->updateOrderNo((string)$rno, $this->currentOrderReference.','.$this->currentOrder);

			$redirect = __PS_BASE_URI__.'order-confirmation.php?id_cart='.(int)$this->context->cart->id.'&id_module='.(int)$this->id.'&id_order='.(int)$this->currentOrder.'&key='.$this->context->cart->secure_key;

			return array('success'=> true, 'redirect' => $redirect );
		}
		catch (Exception $e)
		{
			/*remove invoiceFee if existe*/
			$this->context->cart->deleteProduct((int)Configuration::get('BILLMATE_INV_FEE_ID_'.$this->countries[$country->iso_code]['name']));

 			return array('success'=> false, 'content' => Tools::safeOutput(utf8_encode($e->getMessage())));
		}
	}
    public function hookPaymentReturn($params)
    {
        return $this->display(__FILE__, 'tpl/order-confirmation.tpl');
    }

	public function hookDisplayPaymentReturn($params)
	{
		if ($params['objOrder']->module != $this->name)
			return false;
		$cart = new Cart((int)$params['objOrder']->id_cart);
		if (Validate::isLoadedObject($cart))
		{
			$address = new Address((int)$cart->id_address_invoice);
			$country = new Country((int)$address->id_country);
			$cart->deleteProduct((int)Configuration::get('BILLMATE_INV_FEE_ID_'.$this->countries[$country->iso_code]['name']));
			$cart->save();
		}
		return $this->display(__FILE__, 'tpl/order-confirmation.tpl');
	}

	public function hookDisplayOrderConfirmation($params)
	{
	    
		if ($params['objOrder']->module != $this->name)
			return false;
		if ($params['objOrder'] && Validate::isLoadedObject($params['objOrder']) && isset($params['objOrder']->valid))
		{
			if (isset($params['objOrder']->reference))
				$this->smarty->assign('billmate_order', array('reference' => $params['objOrder']->reference, 'valid' => $params['objOrder']->valid));
			//return $this->display(__FILE__, 'tpl/order-confirmation.tpl');
		}
	}

}
