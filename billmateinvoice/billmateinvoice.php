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

if(!defined('BILLMATE_BASE')){
	define('BILLMATE_BASE', dirname(__FILE__));
}
include_once(BILLMATE_BASE . '/billmate.php');

/**
 * BillmateInvoice class
 *
 * @category  Payment
 * @package   BillMate_Prestashop
 * @author    Gagan Preet <gaganpreet172@gmail.com>
 * @copyright 2013 Eminenece Technology
 * 
 * @link      http://integration.billmate.com/
 */

class BillmateInvoice extends PaymentModule
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
	private $countries = array(
		'SE' => array('name' =>'SWEDEN', 'code' => 'SE', 'langue' => 'SV', 'currency' => 'SEK'),
		'NO' => array('name' =>'NORWAY', 'code' => 'NO', 'langue' => 'NB', 'currency' => 'NOK'),
		'DK' => array('name' =>'DENMARK', 'code' => 'DK', 'langue' => 'DA', 'currency' => 'DKK'),
		'FI' => array('name' =>'FINLAND', 'code' => 'FI', 'langue' => 'FI', 'currency' => 'EUR'),
		//'GB' => array('name' =>'UNITED_KINGDOM', 'code' => 'GB', 'langue' => 'GB', 'currency' => 'GBP'),
		//'US' => array('name' =>'UNITED_STATES', 'code' => 'US', 'langue' => 'US', 'currency' => 'USD'),
	);

	const RESERVED = 1;
	const SHIPPED = 2;
	const CANCEL = 3;
    	
    /**
     * Constructor for BillmateInvoice
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
		if (Configuration::get('BM_INV_STORE_ID_'.$this->countries[$country->iso_code]['name']) > 0
			&& Configuration::get('BM_INV_SECRET_'.$this->countries[$country->iso_code]['name']) != '')
			return true;
		return false;
	}
    public function __construct()
    {
        $this->name = 'billmateinvoice';
        $this->moduleName='billmateinvoice';
        $this->tab = 'payments_gateways';
        $this->version = '1.25';
        $this->author  = 'eFinance Nordic AB';

        $this->currencies = true;
        $this->currencies_mode = 'radio';

        parent::__construct();
        $this->core = null;
        $this->billmate = null;
        $this->country = null;
		$this->limited_countries = array('se', 'onl', 'dk', 'no', 'fi','gb','us'); //, 'no', 'fi', 'dk', 'de', 'nl'

        /* The parent construct is required for translations */
        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('BillMate Invoice');
        $this->description = $this->l('Accepts invoice payments by BillMate');
        $this->confirmUninstall = $this->l(
            'Are you sure you want to delete your settings?'
        );
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
				'value' => Tools::safeOutput(Configuration::get('BM_INV_STORE_ID_'.$country['name'])),
				'type' => 'text',
				'label' => $this->l('E-store ID'),
				'desc' => $this->l(''),
			);
			$input_country[$country['name']]['secret_'.$country['name']] = array(
				'name' => 'billmateSecret'.$country['name'],
				'required' => true,
				'value' => Tools::safeOutput(Configuration::get('BM_INV_SECRET_'.$country['name'])),
				'type' => 'text',
				'label' => $this->l('Secret'),
				'desc' => $this->l(''),
			);
			$input_country[$country['name']]['invoice_fee_'.$country['name']] = array(
				'name' => 'billmateInvoiceFee'.$country['name'],
				'required' => false,
				'value' => Tools::safeOutput(Configuration::get('BM_INV_FEE_'.$country['name'])),
				'type' => 'text',
				'label' => $this->l('Invoice Fee ').'('.$currency['sign'].')',
				'desc' => $this->l(''),
			);
			$input_country[$country['name']]['minimum_value_'.$country['name']] = array(
				'name' => 'billmateMinimumValue'.$country['name'],
				'required' => false,
				'value' => (float)Configuration::get('BM_INV_MIN_VALUE_'.$country['name']),
				'type' => 'text',
				'label' => $this->l('Minimum Value ').'('.$currency['sign'].')',
				'desc' => $this->l(''),
			);
			$input_country[$country['name']]['maximum_value_'.$country['name']] = array(
				'name' => 'billmateMaximumValue'.$country['name'],
				'required' => false,
				'value' => Configuration::get('BM_INV_MAX_VALUE_'.$country['name']) != 0 ? (float)Configuration::get('BM_INV_MAX_VALUE_'.$country['name']) : 99999,
				'type' => 'text',
				'label' => $this->l('Maximum Value ').'('.$currency['sign'].')',
				'desc' => $this->l(''),
			);

			if (Configuration::get('BM_INV_STORE_ID_'.$country['name']))
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
				'billmate_mod' => Configuration::get('BILLMATEINV_MOD'),
				'billmate_active_invoice' => Configuration::get('BILLMATEINV_ACTIVE_INVOICE'),
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
		if ($_POST['billmate_mod'] == 'live')
			Configuration::updateValue('BILLMATEINV_MOD', 0);
		else
			Configuration::updateValue('BILLMATEINV_MOD', 1);


		if (isset($_POST['billmate_active_invoice']) && $_POST['billmate_active_invoice'])
			Configuration::updateValue('BILLMATEINV_ACTIVE_INVOICE', true);
		else
			Configuration::deleteByName('BILLMATEINV_ACTIVE_INVOICE');
		

		foreach ($this->countries as $country)
		{
			Configuration::deleteByName('BM_INV_STORE_ID_'.$country['name']);
			Configuration::deleteByName('BM_INV_SECRET_'.$country['name']);
		}

		$category_id = Configuration::get('BM_INV_CATEID');

		if(!empty($category_id)){
			$sql = 'SELECT COUNT(*) FROM '._DB_PREFIX_.'category where id_category="'.$category_id.'"';
			if( Db::getInstance()->getValue($sql) <= 0){
				$category_id = '';
			}
		}

		if(empty($category_id)){
			$category = new Category();
			$top_category = Category::getRootCategory();
			$category->id_parent = $top_category->id;
			$category->id_category_default = $top_category->id;
			$category->is_root_category = true;
			
			$languages = Language::getLanguages(false);
			$category->active = false;
			
			foreach ($languages as $language)
			{
				$category->name[$language['id_lang']] = 'Category '.$this->getFeeLabel('');
				$category->link_rewrite[$language['id_lang']] = 'billmate_invoice_fee_'.$country['name'];
			}
			if( $category->add() ){
				Configuration::updateValue('BM_INV_CATEID', $category->id);
				$category_id = $category->id;
			}
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
					
					Configuration::updateValue('BM_INV_STORE_ID_'.$country['name'], $storeId);
					Configuration::updateValue('BM_INV_SECRET_'.$country['name'], $secret);
					Configuration::updateValue('BM_INV_FEE_'.$country['name'], (float)Tools::getValue('billmateInvoiceFee'.$country['name']));
					Configuration::updateValue('BM_INV_MIN_VALUE_'.$country['name'], (float)Tools::getValue('billmateMinimumValue'.$country['name']));
					Configuration::updateValue('BM_INV_MAX_VALUE_'.$country['name'], ($_POST['billmateMaximumValue'.$country['name']] != 0 ? (float)Tools::getValue('billmateMaximumValue'.$country['name']) : 99999));
					$id_product = Db::getInstance()->getValue('SELECT `id_product` FROM `'._DB_PREFIX_.'product_lang` WHERE `name` = \''.$this->getFeeLabel($country['name']).'\'');

					$taxeRules = TaxRulesGroup::getAssociatedTaxRatesByIdCountry(Country::getByIso($key));
					$maxiPrice = 0;
					$idTaxe = 0;
					foreach ($taxeRules as $key => $val)
						if ((int)$val > $maxiPrice)
						{
							$maxiPrice = (int)$val;
							$idTaxe = $key;
						}


					if ($id_product != null)
					{
						$productInvoicefee = new Product((int)$id_product);
						$productInvoicefee->id_category_default = $productInvoicefee->default_category = $category_id;
						//$productInvoicefee->addToCategories((int)$category_id);
						$productInvoicefee->price = (float)Tools::getValue('billmateInvoiceFee'.$country['name']);
						if (_PS_VERSION_ >= 1.5)
							StockAvailable::setProductOutOfStock((int)$productInvoicefee->id, true, null, 0);
						if ($idTaxe != 0)
							$productInvoicefee->id_tax_rules_group = (int)$idTaxe;
						$productInvoicefee->update();
					}
					else
					{
						$productInvoicefee = new Product();
						$productInvoicefee->out_of_stock = 1;
						$productInvoicefee->available_for_order = true;
						$productInvoicefee->id_category_default = $productInvoicefee->default_category = $category_id;
						//$productInvoicefee->addToCategories($category_id);
						$productInvoicefee->id_tax_rules_group = 0;
						$languages = Language::getLanguages(false);
						foreach ($languages as $language)
						{
							$productInvoicefee->name[$language['id_lang']] = $this->getFeeLabel($country['name']);
							$productInvoicefee->link_rewrite[$language['id_lang']] = 'invoiceFee'.$country['name'];
						}
						$productInvoicefee->price = (float)Tools::getValue('billmateInvoiceFee'.$country['name']);
						if (_PS_VERSION_ >= 1.5)
							$productInvoicefee->active = false;
						$productInvoicefee->add();
						if (_PS_VERSION_ >= 1.5)
							StockAvailable::setProductOutOfStock((int)$productInvoicefee->id, true, null, 0);
					}
					Db::getInstance()->delete('category_product', 'id_product = '.(int)$productInvoicefee->id);
					$product_cats = array(
						'id_category' => (int)$category_id,
						'id_product' => (int)$productInvoicefee->id,
						'position' => (int)1,
					);
			  
					Db::getInstance()->insert('category_product', $product_cats,false,true,Db::INSERT_IGNORE);
					
					Configuration::updateValue('BM_INV_FEE_ID_'.$country['name'], $productInvoicefee->id);
					$this->_postValidations[] = $this->l('Your account has been updated to be used in ').$country['name'];

				}
			}
		} 
	}
	function getFeeLabel($country){
	    $countrie = array('SWEDEN' => 'Billmate fakturaavgift');
	    if( empty($countrie[$country])){
	        return $this->l('Billmate Invoice Fee - '.$country);
	    }
	    return $countrie[$country];
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
     * Install the Billmate Invoice module
     *
     * @return bool
     */
    public function install()
    {
		if (!parent::install() || !$this->registerHook('payment') || !$this->registerHook('adminOrder') || !$this->registerHook('paymentReturn') || !$this->registerHook('orderConfirmation'))
			return false;


		$this->registerHook('displayPayment');

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
		$version = substr($version, 0, 2);
		
		foreach ($this->countries as $key => $val)
		{
			$country = new Country(Country::getByIso($key));
			$country->active = true;
			$country->update();
		}
		/* The hook "displayMobileHeader" has been introduced in v1.5.x - Called separately to fail silently if the hook does not exist */

      if ($ret) {
            return false;
        }
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
		if ( !Configuration::get('BILLMATEINV_ACTIVE_INVOICE'))
			return false;

        $total = $this->context->cart->getOrderTotal();

        $cart = $params['cart'];
        $address = new Address(intval($cart->id_address_delivery));
        $country = new Country(intval($address->id_country));
        
        $countryname = BillmateCountry::getContryByNumber( BillmateCountry::fromCode($country->iso_code)  );
        $countryname = Tools::strtoupper($countryname);
        $this->context->cart->deleteProduct((int)Configuration::get('BM_INV_FEE_ID_'.$countryname));
       
        $minVal = Configuration::get('BM_INV_MIN_VALUE_'.$countryname);
        $maxVal = Configuration::get('BM_INV_MAX_VALUE_'.$countryname);
        

		if( version_compare(_PS_VERSION_, '1.5', '>=') ){
			$moduleurl = $link->getModuleLink('billmateinvoice', 'validation', array(), true);
		}else{
			$moduleurl = __PS_BASE_URI__.'modules/billmateinvoice/validation.php';
		}
        $smarty->assign('moduleurl', $moduleurl);


        if( $total > $minVal && $total < $maxVal ){
            $customer = new Customer(intval($cart->id_customer));
            $currency = $this->getCurrency(intval($cart->id_currency));

            return $this->display(__FILE__, 'billmateinvoice.tpl');
        }else{
            return false;
        }
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
        return $this->display(__FILE__, 'confirmation.tpl');
    }
}
