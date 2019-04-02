<?php
class CheckoutAddressesStep extends CheckoutAddressesStepCore
{
    protected $bmTemplate = 'module:billmategateway/views/templates/front/checkout/_partials/steps/addresses.tpl';

    public function getTemplate()
    {
        if (Configuration::get('BILLMATE_GETADDRESS')) {
           return $this->bmTemplate;
        }
        return parent::getTemplate();
    }
}