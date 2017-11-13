<?php
    /**
     * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
     *
     * @author    Jesper Johansson jesper@boxedlogistics.se
     * @copyright Billmate AB 2015
     * @license   OpenSource
     */

require_once(_PS_MODULE_DIR_.'billmategateway/billmategateway.php');

class BillmateMethodCheckout extends BillmateGateway {

    public function __construct()
    {
        parent::__construct();
        $this->name                     = 'billmatecheckout';
        $this->module                   = new BillmateGateway();
        $this->displayName              = $this->module->l('Billmate Checkout','cardpay');
        $this->testMode                 = Configuration::get('BILLMATE_CHECKOUT_TESTMODE');
        $this->sort_order               = 1;
        $this->limited_countries        = array('se');
        $this->allowed_currencies       = array('SEK','EUR','DKK','NOK','GBP','USD');
        $this->authorization_method     = Configuration::get('BCARDPAY_AUTHORIZATION_METHOD');
        $this->validation_controller    = $this->context->link->getModuleLink('billmategateway', 'billmateapi', array('method' => 'cardpay'),true);
        $this->icon                     = file_exists(_PS_MODULE_DIR_.'billmategateway/views/img/'.Tools::strtolower($this->context->language->iso_code).'/card.png') ? 'billmategateway/views/img/'.Tools::strtolower($this->context->language->iso_code).'/card.png' : 'billmategateway/views/img/en/card.png';
    }

        /**
         * Returns Payment info for appending in payment methods list
         */
        public function getPaymentInfo($cart)
        {
            /** Do not show Billmate Checkout as payment option in store */
            return false;
        }

        public function getSettings()
        {
            $settings       = array();
            $statuses       = OrderState::getOrderStates((int)$this->context->language->id);
            $currency       = Currency::getDefaultCurrency();
            $statuses_array = array();
            foreach ($statuses as $status) {
                $statuses_array[$status['id_order_state']] = $status['name'];
            }

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

            $settings['billmate_checkout_order_status']  = array(
                'name'     => 'billmate_checkout_order_status',
                'required' => true,
                'type'     => 'select',
                'label'    => $this->l('Set Order Status'),
                'desc'     => $this->l(''),
                'value'    => (Tools::safeOutput(Configuration::get('BILLMATE_CHECKOUT_ORDER_STATUS'))) ? Tools::safeOutput(Configuration::get('BILLMATE_CHECKOUT_ORDER_STATUS')) : Tools::safeOutput(Configuration::get('PS_OS_PAYMENT')),
                'options'  => $statuses_array
            );
            return $settings;

        }
    }