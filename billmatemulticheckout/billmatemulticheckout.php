<?php

/**
 * 2017 Keshva Thakur
 * @author Keshva Thakur
 * @copyright Keshva Thakur
 * @license   https://www.prestashop.com/en/osl-license
 * @version   3.1.5
 */
if (!defined('_PS_VERSION_'))
    exit;

class billmatemulticheckout extends Module {

    public function __construct() {
        $this->name = 'billmatemulticheckout';
        $this->description = 'billmatemulticheckout.';
        $this->tab = 'pricing_promotion';
        $this->version = '3.1.5';
        $this->author = 'Per Ledin ';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->module_key = '';
        $this->_html = '';
        $this->unique_string = '';
        $this->valid = '';
        $this->simple_content_files_location = $this->_path . 'views/';
        $this->ignore_changes_content_changes = false;

        parent::__construct();

        $this->displayName = $this->l('Billmate multi store checkout enable');
        $this->description = $this->l('This module billmate multi store billmate checkout enable.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install() {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);
        if (!parent::install() || !$this->registerHook('header') || !$this->registerHook('displayHome') || !$this->registerHook('footer'))
            return true;
    }

    public function uninstall() {

        if (!parent::uninstall()) {
            return true;
        }
        return true;
    }

    public function getContent() {
        $this->processSubmit();
        return $this->displayForm();
    }
	
	
	
    public function processSubmit() {

        $currency_id = "";
        $currencyc = "";
        if (Tools::isSubmit('submit' . $this->name)) {

           
			$billmatemulticheckout_=Tools::getValue('billmatemulticheckout_');
			
			//echo "<pre>";
			//print_r($billmatemulticheckout_);
			//exit;
		

            foreach ($billmatemulticheckout_ as $keye => $value) {

                                
                $currency_id =$keye;

                if ($value == '1' && $currency_id != "") {					
                    
					Configuration::updateValue('billmatemulticheckout_'.$keye, $value);
                }
                if ($value == '0' && $currency_id != "") {

                    
					Configuration::updateValue('billmatemulticheckout_'.$keye, $value);

                    $this->_html .= $this->displayConfirmation(" Remove product prices decimals disable sucessfully");
                }
            }
          //  exit;
        }
    }

    public function displayForm() {
        $fields_form = array();

        $id_shop = (int) Context::getContext()->shop->id;


	
		$shops_list = array();
		$shops = Shop::getShops();
		foreach ($shops as $shop){
			$shops_list[] = array( 'id_shop' => $shop['id_shop'], 'name' => $shop['name'] );
	    }
		
			
		
		 foreach ($shops_list as $keye => $valuec) {
			 
			 
			
			 $valuec['billmatemulticheckout_'] = Configuration::get('billmatemulticheckout_'.$valuec['id_shop']);
			 $allcurrenciesc[]=$valuec;
			 
		 }
		

         //echo "<pre>";
         //print_r($allcurrenciesc);
         //exit;

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

?>
