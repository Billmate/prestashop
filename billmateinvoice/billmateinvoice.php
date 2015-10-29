<?php

/**
 * Created by PhpStorm.
 * User: jesperjohansson
 * Date: 15-09-09
 * Time: 21:25
 */
require_once(_PS_MODULE_DIR_.'/billmategateway/library/Common.php');

class BillmateInvoice extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'billmateinvoice';
        $this->displayName = 'Billmate Invoice - Support Plugin - No install needed!';
        $this->version    = BILLMATE_PLUGIN_VERSION;
        $this->author     = 'Billmate AB';


    }
}