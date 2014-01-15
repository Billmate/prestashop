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

include_once(_PS_MODULE_DIR_.'/billmateinvoice/billmateinvoice.php');
require(_PS_MODULE_DIR_.'billmatepartpayment/backward_compatibility/backward.php');
class BillmateInvoiceController extends FrontController
{
	public $ssl = true;
	public $ajax = false;
	public function __construct()
	{
		$this->billmate = new BillmateInvoice();
		if (!$this->billmate->active)
			exit;
		parent::__construct();
		self::$smarty->assign('path' , __PS_BASE_URI__.'modules/billmateinvoice');
	}
	
	public function init()
	{
		global $cookie;
		$this->ajax = true;
		if (!$this->billmate->active)
			return ;
		parent::init();
		if (Tools::isSubmit('pno'))
		{
			require _PS_MODULE_DIR_.'/billmateinvoice/controllers/front/getaddress.php';
			$controller = new BillmateInvoiceGetaddressModuleFrontController();
			$controller->context = new stdClass;
			$controller->context->controller = new stdClass;
			$controller->context->controller->module = $this->billmate;
			$controller->context->cart 	 = self::$cart;
			$controller->context->smarty = self::$smarty;
			$controller->context->customer = $cookie->id_customer;
			$controller->module = $this->billmate;
			$controller->init();
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

		if ($this->billmate->verifCountryAndCurrency($country, $currency) )
		{
			$pno = array('SE' => 'yymmdd-nnnn', 'FI' => 'ddmmyy-nnnn', 'DK' => 'ddmmyynnnn', 'NO' => 'ddmmyynnnn', 'DE' => 'ddmmyy', 'NE' => 'ddmmyynnnn');
			self::$smarty->assign('country', $country);
			self::$smarty->assign('pnoValue', $pno[$country->iso_code]);
			self::$smarty->assign('iso_code', strtolower($country->iso_code));

			if(version_compare(_PS_VERSION_,'1.5','<')){
				$this->_path = __PS_BASE_URI__.'modules/'.$this->billmate->moduleName.'/';
//				$this->_path = .__PS_BASE_URI__.'modules/'.$this->billmate->moduleName.'/controllers/front/getaddress.php';
			} else {
				$this->_path = $link->getModuleLink('billmateinvoice', 'getaddress', array('ajax'=> 0), true);
				//Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->billmate->moduleName.'/'
			}
			self::$smarty->assign(
				array(
					'customer_email' => str_replace('%1$s', $customer->email, $this->billmate->l('My email %1$s is accurate and can be used for invoicing.')),
					'eid'    => Configuration::get('BILLMATE_STORE_ID_'.$countries[$country->iso_code]['name']),
					'opc'=> (bool)Configuration::get('PS_ORDER_PROCESS_TYPE') == 1,
					'customer_month' => (int)substr($customer->birthday, 5, 2),
					'years' => $years, 'customer_year' => (int)substr($customer->birthday, 0, 4),
					'street_number' => $houseInfo[1],
					'house_ext' => $houseInfo[2],
					'moduleurl' => __PS_BASE_URI__.'modules/'.$this->billmate->moduleName.'/validation.php',
					'ajaxurl'   => array(
						'path' => __PS_BASE_URI__.'modules/'.$this->billmate->moduleName.'/validation.php', 
						'this_path_ssl' =>  $this->_path
					))

			);
			$total = self::$cart->getOrderTotal() + (float)Product::getPriceStatic((int)Configuration::get('BM_INV_FEE_ID_'.$countries[$country->iso_code]['name']));

			self::$smarty->assign(
				array(
					'total' => $total,
					'fee' => ($type == 'invoice' ? (float)Product::getPriceStatic((int)Configuration::get('BILLMATE_INV_FEE_ID_'.$countries[$country->iso_code]['name'])) : 0)
				)
			);

			self::$smarty->assign('linkTermsCond', (
			$type == 'invoice' ?'https://online.billmate.com/villkor'.($country->iso_code != 'SE' ? '_'.strtolower($country->iso_code) : '').'.yaws?eid='.(int)Configuration::get('BILLMATE_STORE_ID_'.$countries[$country->iso_code]['name']).'&charge='.round((float)Product::getPriceStatic((int)Configuration::get('BILLMATE_INV_FEE_ID_'.$countries[$country->iso_code]['name'])), 2) : 'https://online.billmate.com/account_'.strtolower($country->iso_code).'.yaws?eid='.(int)Configuration::get('BILLMATE_STORE_ID_'.$countries[$country->iso_code]['name'])));
			self::$smarty->assign('payment_type', Tools::safeOutput($type));
			//self::$smarty->assign()
			
			self::$smarty->display(_PS_MODULE_DIR_.'billmateinvoice/tpl/form.tpl');
		}
	}
}

$billmateController = new BillmateInvoiceController();
$billmateController->run();
