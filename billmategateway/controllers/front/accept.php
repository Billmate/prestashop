<?php
/**
 * Created by PhpStorm.
 * User: jesper
 * Date: 15-03-17
 * Time: 15:32
 */

class BillmatepgatewayAcceptModuleFrontController extends ModuleFrontController {

	public function postProcess()
	{

		$eid = Configuration::get('BILLMATE_STORE_ID');
		$secret = Configuration::get('BILLMATE_SECRET');
		$ssl = true;
		$testMode = false;
		$debug = false;
		$billmate = new BillMate($eid,$secret,$ssl,$testMode, $debug);

		$data = $billmate->verify_hash($_POST);
	}

}