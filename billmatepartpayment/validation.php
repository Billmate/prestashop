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
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');


include_once(_PS_MODULE_DIR_.'/billmatepartpayment/billmatepartpayment.php');
require(_PS_MODULE_DIR_.'billmatepartpayment/backward_compatibility/backward.php');
ini_set('display_errors', 1);
error_reporting(E_ALL);
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
		self::$smarty->assign('path', __PS_BASE_URI__.'modules/billmatepartpayment/payment.php');
	}
	
	public function init()
	{
		global $cookie;
		$this->ajax = true;
		$this->display_column_right = true;
		if (!$this->billmate->active)
			return;
		parent::init();
		if (Tools::isSubmit('billmate_pno'))
		{
			require _PS_MODULE_DIR_.'/billmatepartpayment/controllers/front/getaddress.php';
			$controller = new BillmatePartpaymentGetaddressModuleFrontController();
			$controller->context = new stdClass;
			$controller->context->controller = new stdClass;
			$controller->context->controller->module = $this->billmate;
			$controller->context->cart 	 = self::$cart;
			$controller->context->smarty = self::$smarty;
			$controller->context->customer = $cookie->id_customer;
			$controller->init();
			/*$address_invoice = new Address((int)self::$cart->id_address_invoice);
			$country = new Country((int)$address_invoice->id_country);
			if ($country->iso_code == 'DE' && !isset($_POST['billmate_de_accept']))
			{
				$result['error'] = true;
				$result['message'] = 'Please agree your consent';
			}
			else
				$result = $this->billmate->setPayment(Tools::safeOutput(Tools::getValue('type')));

			if (isset($result['error']))
				self::$smarty->assign('error', $result['message']);*/
		}
	}

	public function displayContent()
	{
		global $link;
		parent::displayContent();
	
		$customer = new Customer((int)self::$cart->id_customer);
		$address_invoice = new Address((int)self::$cart->id_address_invoice);
		$country = new Country((int)$address_invoice->id_country);
		$currency = new Currency((int)self::$cart->id_currency);
		$countries = $this->billmate->getCountries();
		$type = Tools::getValue('type');

//		if ($this->billmate->verifCountryAndCurrency($country, $currency))
//		{
			$pno = array('SE' => 'yymmdd-nnnn', 'FI' => 'ddmmyy-nnnn', 'DK' => 'ddmmyynnnn', 'NO' => 'ddmmyynnnn', 'DE' => 'ddmmyy', 'NE' => 'ddmmyynnnn');
			self::$smarty->assign('country', $country);
			self::$smarty->assign('pnoValue', $pno[$country->iso_code]);
			self::$smarty->assign('iso_code', strtolower($country->iso_code));
			$i = 1;
			$days = array();
			while ($i <= 31)
			{
				if ($i < 10)
					$days[] = '0'.$i;
				else
					$days[] = $i;
				$i++;
			}
			$i = 1;
			$months = array();
			while ($i <= 12)
			{
				if ($i < 10)
					$months[] = '0'.$i;
				else
					$months[] = $i;
				$i++;
			}
			$i = 2000;
			$years = array();
			while ($i >= 1910)
				$years[] = $i--;
			$house_info = $this->getHouseInfo($address_invoice->address1);
			if (version_compare(_PS_VERSION_, '1.5', '<'))
				$this->_path = __PS_BASE_URI__.'modules/'.$this->billmate->moduleName.'/';
			else
				$this->_path = $link->getModuleLink('billmatepartpayment', 'getaddress', array('ajax'=> 0), true);

			self::$smarty->assign(array(
					'days' => $days,
					'customer_day' =>	(int)Tools::substr($customer->birthday, 8, 2),
					'months' => $months,
					'customer_email' => str_replace('%1$s', $customer->email, $this->billmate->l('Min e-postadress %1$s är korrekt och kan användas för fakturering.')),
					'eid'    => Configuration::get('BILLMATE_STORE_ID_'.$countries[$country->iso_code]['name']),
					'opc'=> (bool)Configuration::get('PS_ORDER_PROCESS_TYPE') == 1,
					'customer_month' => (int)Tools::substr($customer->birthday, 5, 2),
					'years' => $years, 'customer_year' => (int)Tools::substr($customer->birthday, 0, 4),
					'street_number' => $house_info[1],
					'house_ext' => $house_info[2],
					'modulepath' => __PS_BASE_URI__.'modules/'.$this->billmate->moduleName.'/validation.php',
					'ajaxurl'   => array(
						'path' => __PS_BASE_URI__.'modules/'.$this->billmate->moduleName.'/',
						'this_path_ssl' =>  $this->_path
					)));
			if ($type == 'invoice')
				$total = self::$cart->getOrderTotal() + (float)Product::getPriceStatic((int)Configuration::get('BILLMATE_INV_FEE_ID_'.$countries[$country->iso_code]['name']));
			else
				$total = self::$cart->getOrderTotal();
			self::$smarty->assign(array(
					'total_fee' => $total,
					'fee' => ($type == 'invoice' ? (float)Product::getPriceStatic((int)Configuration::get('BILLMATE_INV_FEE_ID_'.$countries[$country->iso_code]['name'])) : 0)
				));

			if ($type == 'partpayment')
				self::$smarty->assign('accountPrice', $this->getMonthlyCoast(self::$cart, $countries, $country));

			if ($customer->id_gender != 1
				&& $customer->id_gender != 2
				&& $customer->id_gender != 3
				&& ($country->iso_code == 'DE'
					|| $country->iso_code == 'NL'))
				self::$smarty->assign('gender', Gender::getGenders()->getResults());

			self::$smarty->assign('linkTermsCond', (
			$type == 'invoice' ?'https://online.billmate.com/villkor'.($country->iso_code != 'SE' ? '_'.strtolower($country->iso_code) : '').'.yaws?eid='.(int)Configuration::get('BILLMATE_STORE_ID_'.$countries[$country->iso_code]['name']).'&charge='.round((float)Product::getPriceStatic((int)Configuration::get('BILLMATE_INV_FEE_ID_'.$countries[$country->iso_code]['name'])), 2) : 'https://online.billmate.com/account_'.strtolower($country->iso_code).'.yaws?eid='.(int)Configuration::get('BILLMATE_STORE_ID_'.$countries[$country->iso_code]['name'])));
			self::$smarty->assign('payment_type', Tools::safeOutput($type));
			//self::$smarty->assign()
			self::$smarty->display(_PS_MODULE_DIR_.'billmatepartpayment/tpl/form.tpl');
		//}
	}

	public function getMonthlyCoast($cart, $countries, $country)
	{
		if (!$this->billmate->active)
			return;
		$pclass = new pClasses(
			Configuration::get('BILLMATE_STORE_ID_'.$countries[$country->iso_code]['name']),
			Configuration::get('BILLMATE_SECRET_'.$countries[$country->iso_code]['name']),
			$countries[$country->iso_code]['code'],
			$countries[$country->iso_code]['langue'],
			$countries[$country->iso_code]['currency'],
			Configuration::get('BILLMATE_MOD')
		);

		$account_price = array();
		$pclasses = $pclass->getPClasses(Configuration::get('BILLMATE_STORE_ID_'.$countries[$country->iso_code]['name']));
		$total = (float)$cart->getOrderTotal();
		foreach ($pclasses as $val)

			if ($val['minamount'] < $total)
				$account_price[$val['id']] = array(
					'price' => BillmateCalc::calc_monthly_cost($total, $val, BillmateFlags::CHECKOUT_PAGE),
					'month' => (int)$val['months'],
					'description' => htmlspecialchars_decode(Tools::safeOutput($val['description']))
				);

		return $account_price;
	}


	public function getHouseInfo($address)
	{
		if (!preg_match('/^[^0-9]*/', $address, $match))
			return array($address, '', '');

		$address = str_replace($match[0], '', $address);
		$street = trim($match[0]);
		if (Tools::strlen($address == 0)) {
			return array($street, '', '');
		}
		$address_array = explode(' ', $address);

		$housenumber = array_shift($address_array);

		if (count($address_array) == 0)
			return array($street, $housenumber, '');

		$extension = implode(' ', $address_array);
		return array($street, $housenumber, $extension);
	}

}

$billmate_controller = new BillmatePartpaymentController();
$billmate_controller->run();
