<?php

/**
 * Created by PhpStorm.
 * User: jesperjohansson
 * Date: 15-09-09
 * Time: 21:21
 */
require_once(_PS_MODULE_DIR_.'/billmategateway/library/Common.php');

class BillmatePartpay extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'billmatepartpay';
        $this->displayName = $this->l('Billmate Part Pay');
        $this->description = 'Support plugin - No install needed!';

        $this->version    = BILLMATE_PLUGIN_VERSION;
        $this->author     = 'Billmate AB';
    }
}