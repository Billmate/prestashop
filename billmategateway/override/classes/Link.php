<?php

class Link extends LinkCore
{
    public function getPageLink($controller, $ssl = null, $id_lang = null, $request = null, $request_url_encode = false, $id_shop = null, $relative_protocol = false)
    {
        if (    $controller == 'order-opc'
                && Configuration::get('BILLMATE_CHECKOUT_ACTIVATE') == 1
                && class_exists('BillmateGateway') == true
                && class_exists('BillmategatewayBillmatecheckoutModuleFrontController') == true
        ) {
            $return = $this->getBaseLink($id_shop, $ssl, $relative_protocol).'module/billmategateway/billmatecheckout';
        } else {
            $return = parent::getPageLink($controller, $ssl, $id_lang, $request, $request_url_encode, $id_shop, $relative_protocol);
        }
        return $return;
    }
}
?>