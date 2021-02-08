<?php
class CheckoutAddressesStep extends CheckoutAddressesStepCore
{
    /**
     * @var string
     */
    protected $bmTemplate = 'module:billmategateway/views/templates/front/checkout/common/addresses.tpl';

    /**
     * @return string
     */
    public function getTemplate()
    {
        if ($this->isGetAddressEnabled()) {
           return $this->bmTemplate;
        }
        return parent::getTemplate();
    }

    /**
     * @return array
     */
    public function getTemplateParameters()
    {
        $templateParams = parent::getTemplateParameters();

        if ($this->isGetAddressEnabled()) {
            $templateParams['pno'] = (isset($this->context->cookie->billmatepno)) ?$this->context->cookie->billmatepno : '';
        }

        return $templateParams;
    }

    /**
     * @return bool
     */
    protected function isGetAddressEnabled()
    {
        return (bool)Configuration::get('BILLMATE_GETADDRESS');
    }
}
