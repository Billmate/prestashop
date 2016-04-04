<?php
/**
 * Created by PhpStorm.
 *
 * User: jesperjohansson
 * Date: 15-09-09
 * Time: 21:25
 * @author Jesper Johansson <jesper.johansson@billmate.se>
 * @licence MTL
 * @copyright Billmate AB
 */

require_once(_PS_MODULE_DIR_.'/billmategateway/library/Common.php');

class BillmateInvoice extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'billmateinvoice';
        $this->displayName = $this->l('Billmate Invoice');
        $this->description = 'Support plugin - No install needed!';

        $this->version    = BILLMATE_PLUGIN_VERSION;
        $this->author     = 'Billmate AB';

    }
}
