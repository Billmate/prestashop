<?php
/**
 * Created by PhpStorm.
 * User: Boxedsolutions
 * Date: 2017-03-20
 * Time: 08:14
 */
require_once(_PS_MODULE_DIR_.'/billmategateway/library/Common.php');

class BillmateCheckout extends PaymentModule{

    protected $postValidations;
    protected $postErrors;

    public function __construct()
    {
        $this->name = 'billmatecheckout';
        $this->moduleName = 'billmatecheckout';
        $this->displayName = $this->l('Billmate Checkout');
        $this->description = $this->l('Adds billmate Checkout');
        $this->version    = BILLMATE_PLUGIN_VERSION;
        $this->tab        = 'payments_gateways';
        $this->author     = 'Billmate AB';
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
        parent::__construct();
        $this->page                 = basename(__FILE__, '.php');
        $this->context->smarty->assign('base_dir', __PS_BASE_URI__);


    }

    public function install()
    {
        if (!parent::install())
            return false;

        if (!$this->registerHooks())
            return false;

        if (!function_exists('curl_version'))
        {
            $this->_errors[] = $this->l('Sorry, this module requires the cURL PHP Extension (http://www.php.net/curl), which is not enabled on your server. Please ask your hosting provider for assistance.');
            return false;
        }

        return true;

    }

    public function registerHooks()
    {
        return $this->registerHook('displayPayment') &&
        $this->registerHook('payment') &&
        $this->registerHook('paymentReturn') &&
        $this->registerHook('orderConfirmation') &&
        $this->registerHook('actionOrderStatusUpdate') &&
        $this->registerHook('displayBackOfficeHeader') && $this->registerHook('header') && $this->registerHook('adminTemplate');
    }


    public function getContent()
    {
        $html = '';

        if (!empty($_POST) && Tools::getIsset('billmateSubmit'))
        {
            $this->_postValidation();
            if (count($this->postValidations))
                $html .= $this->displayValidations();

            if (count($this->postErrors))
                $html .= $this->displayErrors();

        }

        $html .= $this->displayAdminTemplate();

        return $html;
    }

    public function displayValidations()
    {
        return '';
    }

    public function displayErrors()
    {
        $this->smarty->assign('billmateError', $this->postErrors);

        return $this->display(__FILE__, 'error.tpl');
    }

    public function displayAdminTemplate()
    {
        $tab   = array();
        $tab[] = array(
            'title'    => $this->l('Settings'),
            'content'  => $this->getGeneralSettings(),
            'icon'     => '../modules/'.$this->moduleName.'/views/img/icon-settings.gif',
            'tab'      => 1,
            'selected' => true,

        );
        $i     = 2;
        
        $this->smarty->assign('FormCredential', './index.php?tab=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module='.$this->tab.'&module_name='.$this->name);
        $this->smarty->assign('tab', $tab);
        $this->smarty->assign('moduleName', $this->moduleName);
        $this->smarty->assign($this->moduleName.'Logo', '../modules/'.$this->moduleName.'/views/img/logo.png');
        $this->smarty->assign('js', array('../modules/'.$this->moduleName.'/views/js/billmate.js'));

        $this->smarty->assign('stylecss', '../modules/billmategateway/views/css/billmate.css');

        return $this->display(__FILE__, 'admin.tpl');
    }

    public function hookAdminTemplate()
    {
        return $this->getGeneralSettings();
    }

    /**
     * The method takes care of Validation and persisting the posted data
     */
    public function _postValidation()
    {
        // General Settings

        $billmateId     = Tools::getValue('billmateId');
        $billmateSecret = Tools::getValue('billmateSecret');

        $credentialvalidated = false;
        if ($this->validateCredentials($billmateId, $billmateSecret))
        {
            $credentialvalidated = true;
            Configuration::updateValue('BILLMATE_ID', $billmateId);
            Configuration::updateValue('BILLMATE_SECRET', $billmateSecret);
        }
        Configuration::updateValue('BILLMATE_CHECKOUT_ACTIVATE', Tools::getIsset('billmate_checkout_active') ? 1 : 0);

        Configuration::updateValue('BILLMATE_CHECKOUT_TESTMODE',Tools::getIsset('billmate_checkout_testmode') ? 1 : 0);




        Configuration::updateValue('BILLMATE_CHECKOUT_SEND_REFERENCE', Tools::getValue('sendOrderReference'));

    }


    public function validateCredentials($eid, $secret)
    {
        if (empty($eid))
        {
            $this->postErrors[] = $this->l('You must insert a Billmate ID');
            return false;
        }

        if (empty($secret))
        {
            $this->postErrors[] = $this->l('You must insert a Billmate Secret');
            return false;
        }


        $billmate            = Common::getBillmate($eid, $secret, false);
        $data                = array();
        $data['PaymentData'] = array(
            'currency' => 'SEK',
            'language' => 'sv',
            'country'  => 'se'
        );
        $result              = $billmate->getPaymentplans($data);
        if (isset($result['code']) && ($result['code'] == '9010' || $result['code'] == '9012' || $result['code'] == '9013'))
        {
            $this->postErrors[] = utf8_encode($result['message']);

            return false;
        }

        return true;
    }
    public function hookHeader()
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return;
        }
        $this->context->controller->addCSS(__DIR__.'/views/css/checkout.css', 'all');
        error_log('folder'.__DIR__);
        if (Configuration::get('BILLMATE_CHECKOUT_ACTIVATE')) {
            $this->context->controller->addJS(__DIR__.'/views/js/checkout.js');
            $this->smarty->assign(
                'billmate_checkout_url',
                $this->context->link->getModuleLink('billmatecheckout', 'billmatecheckout', array(), true)
            );

            return $this->display(__FILE__, 'header.tpl');
        }
    }

    public function getGeneralSettings()
    {
        $settings       = array();
        $statuses       = OrderState::getOrderStates((int)$this->context->language->id);
        $statuses_array = array();
        foreach ($statuses as $status)
            $statuses_array[$status['id_order_state']] = $status['name'];

        $settings['billmateid'] = array(
            'name'     => 'billmateId',
            'required' => true,
            'type'     => 'text',
            'label'    => $this->l('Billmate ID'),
            'desc'     => $this->l('The Billmate ID from Billmate Online'),
            'value'    => Configuration::get('BILLMATE_ID'),
        );

        $settings['billmatesecret'] = array(
            'name'     => 'billmateSecret',
            'required' => true,
            'type'     => 'text',
            'label'    => $this->l('Secret'),
            'desc'     => $this->l('The secret key from Billmate Online'),
            'value'    => Configuration::get('BILLMATE_SECRET')
        );

        $reference = array('orderid' => $this->l('Order ID'),'reference' => $this->l('Reference ID'));
        $settings['sendorderreference'] = array(
            'name'     => 'sendOrderReference',
            'required' => true,
            'type'     => 'select',
            'label'    => $this->l('Order id used by Billmate'),
            'desc'     => '',
            'value'    => Configuration::get('BILLMATE_SEND_REFERENCE') ? Configuration::get('BILLMATE_SEND_REFERENCE') : 'orderid',
            'options' => $reference
        );

        $activate_status      = Configuration::get('BILLMATE_CHECKOUT_ACTIVATE');
        $settings['billmate_checkout_active'] = array(
            'name'     => 'billmate_checkout_active',
            'required' => true,
            'type'     => 'checkbox',
            'label'    => $this->l('Billmate Checkout Active'),
            'desc'     => $this->l('Activate Billmate checkout'),
            'value'    => $activate_status
        );

        $testmode_status      = Configuration::get('BILLMATE_CHECKOUT_TESTMODE');

        $settings['billmate_checkout_testmode'] = array(
            'name'     => 'billmate_checkout_testmode',
            'required' => true,
            'type'     => 'checkbox',
            'label'    => $this->l('Testmode'),
            'desc'     => $this->l('Run Checkout in testmode'),
            'value'    => $testmode_status
        );
        
        $this->smarty->assign(array('settings' => $settings, 'moduleName' => $this->l('Common Settings')));


        return $this->display(__FILE__, 'settings.tpl');
    }

    public function hookPaymentReturn($params)
    {
        return $this->hookOrderConfirmation($params);
    }

    public function hookOrderConfirmation($params)
    {
        return $this->display(__FILE__, 'orderconfirmation.tpl');
    }
    

}