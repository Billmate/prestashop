<?php
	/**
	 * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
	 *
	 * @author    Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 * @license   OpenSource
	 */

	/*
	 * The controller for canceled payments
	 */

	require_once(_PS_MODULE_DIR_.'billmategateway/library/Common.php');

	class BillmategatewayGetaddressModuleFrontController extends ModuleFrontController{
		public $ajax = true;
		public $ssl = true;

		protected $pno;
		protected $billmate;

		public function postProcess()
		{
			$response = array();
			$eid    = Configuration::get('BILLMATE_ID');
			$secret = Configuration::get('BILLMATE_SECRET');
			if (!defined('BILLMATE_LANGUAGE')) define('BILLMATE_LANGUAGE', $this->context->language->iso_code);

			$this->billmate = Common::getBillmate($eid, $secret, false);

			$this->pno = Tools::getValue('pno');

			$address = $this->billmate->getAddress(array('pno' => $this->pno));
			if (!isset($address['code']))
			{
				$response['success'] = true;
				$encoded_address = array();
				foreach ($address as $key => $row)
					$encoded_address[$key] = mb_convert_encoding($row,'UTF-8','auto');

				$country_id = Country::getIdByName($this->context->language->id, $encoded_address['country']);
				$encoded_address['id_country'] = $country_id;
				$response['data'] = $encoded_address;

			}
			else
			{
				$response['success'] = false;
				$response['message'] = mb_convert_encoding($address['message'], 'UTF-8', 'auto');

			}
			die(Tools::jsonEncode($response));

		}
	}