<?php

class Link extends LinkCore
{
    public function getPageLink($controller, $ssl = null, $id_lang = null, $request = null, $request_url_encode = false, $id_shop = null, $relative_protocol = false)
    {
        $isController = false;
        $isController = ($controller == 'order-opc') ? true : $isController;
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            if ($controller == 'order') {
                $isController = true;
            }
        }
        if (    $isController == true
            && Module::isInstalled('billmategateway')
            && Module::isEnabled('billmategateway')
            && version_compare(Configuration::get('BILLMATE_VERSION'), '3.0.0', '>=')
            && Configuration::get('BILLMATE_CHECKOUT_ACTIVATE') == 1
        ) {
            $return = $this->getModuleLink('billmategateway', 'billmatecheckout');
        } else {
            $return = parent::getPageLink($controller, $ssl, $id_lang, $request, $request_url_encode, $id_shop, $relative_protocol);
        }
        return $return;
    }
}
?>