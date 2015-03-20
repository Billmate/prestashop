<?php
/**
 * Created by PhpStorm.
 * User: jesper
 * Date: 15-03-17
 * Time: 15:31
 */

class BillmategatewayCallbackModuleFrontController extends ModuleFrontController {

	public function postProcess()
	{
		$eid = Configuration::get('BILLMATE_STORE_ID');
		$secret = Configuration::get('BILLMATE_SECRET');
		$ssl = true;
		$testMode = false;
		$debug = false;
		$billmate = new BillMate($eid,$secret,$ssl,$testMode, $debug);

		$data = $billmate->verify_hash(Tools::jsonDecode(Tools::file_get_contents('php://input')));
	}

}