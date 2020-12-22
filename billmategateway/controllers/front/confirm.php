<?php

class BillmategatewayConfirmModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $orderId = Tools::getValue('oid');

        $order = ($orderId) ? new Order($orderId) : null;

        if ($order) {
            $orderPresenter = new OrderPresenter();

            $order = $orderPresenter->present($order);
        }

        $this->setTemplate(
            !empty($order) ? $this->getDefaultTemplatePath() : $this->getCustomTemplatePath()
        );

        $this->context->smarty->assign(array(
            'HOOK_HEADER' => Hook::exec('displayHeader'),
            'order' => $order,
        ));
    }

    private function getDefaultTemplatePath()
    {
        return (version_compare(_PS_VERSION_,'1.7','>=')) ?
            'checkout/order-confirmation' :
            _PS_THEME_DIR_ . 'order-confirmation.tpl';
    }

    private function getCustomTemplatePath()
    {
        return (version_compare(_PS_VERSION_,'1.7','>=')) ?
            'module:billmategateway/views/templates/front/confirm/1.7/confirm.tpl' :
            'confirm/1.6/confirm.tpl';
    }
}
