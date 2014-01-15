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
error_reporting(null);
ini_set('display_errors', 0);
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

include_once(_PS_MODULE_DIR_.'/billmatepartpayment/billmatepartpayment.php');
require(_PS_MODULE_DIR_.'billmatepartpayment/backward_compatibility/backward.php');

class BillmatePartpaymentController extends FrontController
{
	public $ssl = true;
	public $ajax = false;
	public function __construct()
	{
		$this->billmate = new BillmatePartpayment();
		if (!$this->billmate->active)
			exit;
		parent::__construct();
		self::$smarty->assign('path' , __PS_BASE_URI__.'modules/billmatepartpayment/payment.php');
	}
	
	public function init()
	{
		global $cookie;
		//$this->ajax = true;
		if (!$this->billmate->active)
			return ;
		parent::init();
		if (Tools::isSubmit('billmate_pno'))
		{
			
			require _PS_MODULE_DIR_.'billmatepartpayment/controllers/front/getaddress.php';
			$controller = new BillmatePartpaymentGetaddressModuleFrontController();
			$controller->context = new stdClass;
			$controller->context->controller = new stdClass;
			$controller->context->controller->module = $this->billmate;
			$controller->context->cart 	 = self::$cart;
			$controller->context->smarty = self::$smarty;
			$controller->context->customer = $cookie->id_customer;
			$controller->init();
		}
	}

	public function displayContent()
	{
		global $link, $cookie;
		parent::displayContent();
		$customer = new Customer((int)self::$cart->id_customer);
		$address_invoice = new Address((int)self::$cart->id_address_invoice);
		$country = new Country((int)$address_invoice->id_country);
		$currency = new Currency((int)self::$cart->id_currency);
		$countries = $this->billmate->getCountries();
		$type = Tools::getValue('type');

		if ($this->billmate->verifCountryAndCurrency($country, $currency) && ($type == 'invoice' || $type == 'account' || $type == 'special'))
		{
			$pno = array('SE' => 'yymmdd-nnnn', 'FI' => 'ddmmyy-nnnn', 'DK' => 'ddmmyynnnn', 'NO' => 'ddmmyynnnn', 'DE' => 'ddmmyy', 'NE' => 'ddmmyynnnn');
			self::$smarty->assign('country', $country);
			self::$smarty->assign('pnoValue', $pno[$country->iso_code]);
			self::$smarty->assign('iso_code', strtolower($country->iso_code));
			$i = 1;
			while ($i <= 31)
			{
				if ($i < 10)
					$days[] = '0'.$i;
				else
					$days[] = $i;
				$i++;
			}
			$i = 1;
			while ($i <= 12)
			{
				if ($i < 10)
					$months[] = '0'.$i;
				else
					$months[] = $i;
				$i++;
			}
			$i = 2000;
			while ($i >= 1910)
				$years[] = $i--;
			$houseInfo = $this->getHouseInfo($address_invoice->address1);
			if(version_compare(_PS_VERSION_,'1.5','<')){
				$this->_path = __PS_BASE_URI__.'modules/'.$this->billmate->moduleName.'/payment.php?do=address&ajax=true';
//				$this->_path = .__PS_BASE_URI__.'modules/'.$this->billmate->moduleName.'/controllers/front/getaddress.php';
			} else {
				$this->_path = $link->getModuleLink('billmatepartpayment', 'getaddress', array('ajax'=> 0), true);
				//Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->billmate->moduleName.'/'
			}
			self::$smarty->assign(
				array(
					'days' => $days,
					'customer_day' =>	(int)substr($customer->birthday, 8, 2),
					'months' => $months,
					'customer_email' => str_replace('%1$s', $customer->email, $this->billmate->l('My email %1$s is accurate and can be used for invoicing.')),
					'eid'    => Configuration::get('BILLMATE_STORE_ID_'.$countries[$country->iso_code]['name']),
					'opc'=> (bool)Configuration::get('PS_ORDER_PROCESS_TYPE') == 1,
					'customer_month' => (int)substr($customer->birthday, 5, 2),
					'years' => $years, 'customer_year' => (int)substr($customer->birthday, 0, 4),
					'street_number' => $houseInfo[1],
					'house_ext' => $houseInfo[2],
					'modulepath' => __PS_BASE_URI__.'modules/'.$this->billmate->moduleName.'/payment.php',
					'ajaxurl'   => array(
						'path' => __PS_BASE_URI__.'modules/'.$this->billmate->moduleName.'/', 
						'this_path_ssl' =>  $this->_path
					))

			);
			if ($type == 'invoice')
				$total = self::$cart->getOrderTotal() + (float)Product::getPriceStatic((int)Configuration::get('BILLMATE_INV_FEE_ID_'.$countries[$country->iso_code]['name']));
			else
				$total = self::$cart->getOrderTotal();
			self::$smarty->assign(
				array(
					'total_fee' => $total,
					'fee' => ($type == 'invoice' ? (float)Product::getPriceStatic((int)Configuration::get('BILLMATE_INV_FEE_ID_'.$countries[$country->iso_code]['name'])) : 0)
				)
			);

			if ($type == 'account')
				self::$smarty->assign('accountPrice', $this->getMonthlyCoast(self::$cart, $countries, $country));

			if ($customer->id_gender != 1 && $customer->id_gender != 2 && $customer->id_gender != 3 && ($country->iso_code == 'DE' || $country->iso_code == 'NL'))
				self::$smarty->assign('gender', Gender::getGenders()->getResults());

			self::$smarty->assign('linkTermsCond', (
			$type == 'invoice' ?'https://online.billmate.com/villkor'.($country->iso_code != 'SE' ? '_'.strtolower($country->iso_code) : '').'.yaws?eid='.(int)Configuration::get('BILLMATE_STORE_ID_'.$countries[$country->iso_code]['name']).'&charge='.round((float)Product::getPriceStatic((int)Configuration::get('BILLMATE_INV_FEE_ID_'.$countries[$country->iso_code]['name'])), 2) : 'https://online.billmate.com/account_'.strtolower($country->iso_code).'.yaws?eid='.(int)Configuration::get('BILLMATE_STORE_ID_'.$countries[$country->iso_code]['name'])));
			self::$smarty->assign('payment_type', Tools::safeOutput($type));
			//self::$smarty->assign()
			$extra = '.tpl';
			//if( $this->context->getMobileDevice() ) $extra = '-mobile.tpl';

			self::$smarty->display(_PS_MODULE_DIR_.'billmatepartpayment/tpl/form'.$extra);
		}
	}

	public function getMonthlyCoast($cart, $countries, $country)
	{
		if (!$this->billmate->active)
			return ;
		$billmate = new Billmate();
		$billmate->config(
			Configuration::get('BILLMATE_STORE_ID_'.$countries[$country->iso_code]['name']),
			Configuration::get('BILLMATE_SECRET_'.$countries[$country->iso_code]['name']),
			$countries[$country->iso_code]['code'],
			$countries[$country->iso_code]['langue'],
			$countries[$country->iso_code]['currency'],
			Configuration::get('BILLMATE_MOD'),
			'mysql',
			array(
				'user' => _DB_USER_,
				'passwd' => _DB_PASSWD_,
				'dsn' => _DB_SERVER_,
				'db' => _DB_NAME_,
				'table' => _DB_PREFIX_.'billmate_payment_pclasses'
			));

		$accountPrice = array();
		$pclasses = array_merge($billmate->getPClasses(BillmatePClass::ACCOUNT), $billmate->getPClasses(BillmatePClass::CAMPAIGN));
		$total = (float)$cart->getOrderTotal();
		foreach ($pclasses as $val)
			if ($val->getMinAmount() < $total)
				$accountPrice[$val->getId()] = array('price' => BillmateCalc::calc_monthly_cost($total, $val, BillmateFlags::CHECKOUT_PAGE), 'month' => (int)$val->getMonths(), 'description' => htmlspecialchars_decode(Tools::safeOutput($val->getDescription())));

		return $accountPrice;
	}


	public function getHouseInfo($address)
	{
		if (!preg_match('/^[^0-9]*/', $address, $match))
			return array($address, '', '');

		$address = str_replace($match[0], '', $address);
		$street = trim($match[0]);
		if (strlen($address == 0)) {
			return array($street, '', '');
		}
		$addrArray = explode(' ', $address);

		$housenumber = array_shift($addrArray);

		if (count($addrArray) == 0) {
			return array($street, $housenumber, '');
		}

		$extension = implode(' ', $addrArray);
		return array($street, $housenumber, $extension);
	}

}

$billmateController = new BillmatePartpaymentController();
$billmateController->run();
