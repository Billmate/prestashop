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
        parent::__construct();

        $this->name         = 'billmatecheckout';
        $this->moduleName   = 'billmatecheckout';
        $this->displayName  = $this->l('Billmate Checkout');
        $this->description  = 'Support plugin - No install needed!';

        $this->version      = BILLMATE_PLUGIN_VERSION;
        $this->author       = 'Billmate AB';
        $this->page         = basename(__FILE__, '.php');

        $this->context->smarty->assign('base_dir', __PS_BASE_URI__);
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        $db = Db::getInstance();
        $db->execute('DELETE FROM '._DB_PREFIX_.'module WHERE name = "billmatecheckout";');
        $db->execute('INSERT INTO '._DB_PREFIX_.'module (name,active,version) VALUES("billmatecheckout",1,"2.0.0");');
    }

}
