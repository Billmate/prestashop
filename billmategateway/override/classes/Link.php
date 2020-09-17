<?php

/**
 * Class Link extends LinkCore
 */
class Link extends LinkCore
{

    /**
     * @param $controller
     * @param null $ssl
     * @param null $id_lang
     * @param null $request
     * @param false $request_url_encode
     * @param null $id_shop
     * @param false $relative_protocol
     * @return mixed
     */
    public function getPageLink($controller, $ssl = null, $id_lang = null, $request = null, $request_url_encode = false, $id_shop = null, $relative_protocol = false)
    {
        if (strpos($_SERVER['REQUEST_URI'], 'order') == false) {
            if ($this->isBMCheckoutEnabled($controller)) {
                return $this->getModuleLink('billmategateway', 'billmatecheckout');
            }

            $id_shop = (int)Context::getContext()->shop->id;
            $controller_name = Tools::getValue('controller');
            $enable_billmate_other_store = Configuration::get('billmatemulticheckout_' . $id_shop);

            if ($id_shop == $enable_billmate_other_store && $controller_name == "billmatecheckout") {
                return $this->getModuleLink('billmategateway', 'billmatecheckout');
            }
        }

        return parent::getPageLink($controller, $ssl, $id_lang, $request, $request_url_encode, $id_shop, $relative_protocol);
    }

    /**
     * @param $controller
     *
     * @return bool
     */ 
    public function isBMCheckoutEnabled($controller)
    {
        $bmModule = $this->getBmModule();
        if (is_object($bmModule) && method_exists($bmModule, 'isBmCheckoutEnabled')){
            return $bmModule->isBmCheckoutEnabled($controller);
        }
        return false;
    }

    /**
     * @param $controller
     *
     * @return bool
     */
      protected function getBmModule()
    {
        return Module::getInstanceByName('billmategateway');
    }
}