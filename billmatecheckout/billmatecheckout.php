<?php
/**
 * Created by PhpStorm.
 * User: Boxedsolutions
 * Date: 2017-03-20
 * Time: 08:14
 */
require_once(_PS_MODULE_DIR_.'/billmategateway/library/Common.php');

class BillmateCheckout extends PaymentModule{
    
    public function __construct()
    {
        $this->name = 'billmatecheckout';
        $this->displayName = $this->l('Billmate Checkout');
        $this->description = $this->l('Adds billmate Checkout');
        $this->version    = BILLMATE_PLUGIN_VERSION;
        $this->author     = 'Billmate AB';

    }
}