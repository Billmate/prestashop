<?php
/**
 * Created by PhpStorm.
 * User: jesperjohansson
 * Date: 15-09-09
 * Time: 21:18
 */

require_once(_PS_MODULE_DIR_.'/billmategateway/library/Common.php');

class BillmateBankpay extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'billmatebankpay';
        $this->displayName = $this->l('Billmate Bankpay');
        $this->description = 'Support plugin - No install needed!';

        $this->version    = BILLMATE_PLUGIN_VERSION;
        $this->author     = 'Billmate AB';


    }
}
