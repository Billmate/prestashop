<?php
/**
 * Created by PhpStorm.
 * User: Boxedsolutions
 * Date: 2017-09-27
 * Time: 02:43
 */
require_once(_PS_MODULE_DIR_.'/billmategateway/library/Common.php');

class billmateswish extends PaymentModule
{
	public function __construct()
	{
		$this->name = 'billmateswish';
		$this->displayName = $this->l('Billmate Swish');
		$this->description = 'Support plugin - No install needed! Invoice service';

		$this->version    = BILLMATE_PLUGIN_VERSION;
		$this->author     = 'Billmate AB';


	}
}