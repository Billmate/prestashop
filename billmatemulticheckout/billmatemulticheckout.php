<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Multi store Checkout for Billmate
 */
class billmatemulticheckout extends Module {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->name         = 'billmatemulticheckout';
        $this->moduleName   = 'billmatemulticheckout';
        $this->displayName = $this->l('Billmate multi store checkout enable');
        $this->description = $this->l('This module billmate multi store billmate checkout enable.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->version      = BILLMATE_PLUGIN_VERSION;
        $this->author       = 'Billmate AB';
        $this->page         = basename(__FILE__, '.php');

        $this->context->smarty->assign('base_dir', __PS_BASE_URI__);
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);

        $this->bootstrap = true;
        $this->need_instance = 0;
        $this->simple_content_files_location = $this->_path . 'views/';
        $this->ignore_changes_content_changes = false;
    }

    /**
     * Install process
     *
     * @return bool
     */
    public function install() {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        if (!parent::install() || !$this->registerHook('header') || !$this->registerHook('displayHome') || !$this->registerHook('footer')) {
            return true;
        }
    }

    /**
     * Uninstall process
     *
     * @return bool
     */
    public function uninstall() {
        if (!parent::uninstall()) {
            return true;
        }
        return true;
    }

    /**
     * Get the content
     *
     * @return string
     */
    public function getContent() {
        $this->processSubmit();
        return $this->displayForm();
    }

    /**
     * Process the submit action
     *
     * @retrun void
     */
    public function processSubmit() {
        $currency_id = '';
        $currencyc = '';
        if (Tools::isSubmit('submit' . $this->name)) {
            $billmatemulticheckout_ = Tools::getValue('billmatemulticheckout_');
            foreach ($billmatemulticheckout_ as $key => $value) {
                $currency_id = $key;
                if ($value == '1' && $currency_id != '') {
                    Configuration::updateValue('billmatemulticheckout_' . $key, $value);
                }
                if ($value == '0' && $currency_id != '') {
                    Configuration::updateValue('billmatemulticheckout_' . $key, $value);
                    $this->_html .= $this->displayConfirmation(" Remove product prices decimals disable sucessfully");
                }
            }
        }
    }

    /**
     * Display the form
     *
     * @return string
     */
    public function displayForm() {
        $fields_form = array();
        $id_shop = (int)Context::getContext()->shop->id;
        $shops_list = array();
        $shops = Shop::getShops();
        foreach ($shops as $shop){
            $shops_list[] = array( 'id_shop' => $shop['id_shop'], 'name' => $shop['name'] );
        }

        foreach ($shops_list as $key => $value) {
             $value['billmatemulticheckout_'] = Configuration::get('billmatemulticheckout_' . $value['id_shop']);
             $allcurrenciesc[] = $value;
        }

        $fields_form[]['form'] = array(
            'input' => array(
                array(
                    'name' => 'topform',
                    'type' => 'topform',
                    'billmatemulticheckout' => Configuration::get('billmatemulticheckout'),
                    'currencies' => $allcurrenciesc
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        return $this->_html . $helper->generateForm($fields_form);
    }
}
