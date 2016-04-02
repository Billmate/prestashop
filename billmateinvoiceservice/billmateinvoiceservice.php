<?php
/**
 * Created by Boxedsolutions.
 * Author: Jesper Johansson <jesper@boxedlogistics.se>
 * Project: prestashopNew
 * Date: 2016-04-02
 * Time: 18:29
 */
require_once(_PS_MODULE_DIR_.'/billmategateway/library/Common.php');

class billmateinvoiceservice extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'billmateinvoice';
        $this->displayName = $this->l('Billmate Invoice');
        $this->description = 'Support plugin - No install needed! Invoice service';

        $this->version    = BILLMATE_PLUGIN_VERSION;
        $this->author     = 'Billmate AB';


    }
}