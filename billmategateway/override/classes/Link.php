<?php
class Link extends LinkCore
{

    public function getPageLink($controller, $ssl = null, $id_lang = null, $request = null, $request_url_encode = false, $id_shop = null, $relative_protocol = false)
    {
        if (strpos($_SERVER['REQUEST_URI'], 'order') == false) {
            if ($this->isBMCheckoutEnabled($controller)) {
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
    protected function isBMCheckoutEnabled($controller)
    {
        $bmModule = $this->getBmModule();
        if (is_object($bmModule) && method_exists($bmModule, 'isBmCheckoutEnabled')){
            return $bmModule->isBmCheckoutEnabled($controller);
        }
        return false;
    }

    /**
     * @return BillmateGateway
     */
    protected function getBmModule()
    {
        return Module::getInstanceByName('billmategateway');
    }
}