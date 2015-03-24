<?php

	require_once(_PS_MODULE_DIR_.'billmategateway/library/Common.php');

	class BillmategatewayGetaddressModuleFrontController extends ModuleFrontController{
		protected $ajax = true;
		protected $ssl = true;

		protected $pno;
		protected $billmate;

		public function postProcess()
		{
			$eid    = Configuration::get('BILLMATE_ID');
			$secret = Configuration::get('BILLMATE_SECRET');
			$this->billmate = Common::getBillmate($eid,$secret,false);

			$this->pno = Tools::getValue('pno');

			$address = $this->billmate->getAddress(array('pno' => $this->pno));
			if (!isset($address['code']))
			{
				$response['success'] = true;
				foreach($address as $key => $row)
					$encoded_address[$key] = mb_convert_encoding($row,'UTF-8','auto');
				$response['data'] = $encoded_address;

			}
			else
			{
				$response['success'] = false;
				$response['message'] = mb_convert_encoding($address['message'],'UTF-8','auto');

			}
			die(Tools::jsonEncode($response));

		}
	}