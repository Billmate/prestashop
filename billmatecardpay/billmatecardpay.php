<?php

/**
 * Created by PhpStorm.
 * User: jesperjohansson
 * Date: 15-09-09
 * Time: 21:27
 */
require_once(_PS_MODULE_DIR_.'/billmategateway/library/Common.php');

class BillmateCardpay extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'billmatecardpay';
        $this->displayName = 'Billmate Cardpay - Support Plugin - No install needed!';
        $this->version    = BILLMATE_PLUGIN_VERSION;
        $this->author     = 'Billmate AB';



    }
}