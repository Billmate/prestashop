<?php

/**
 * Created by PhpStorm.
 * User: Boxedsolutions
 * Date: 2017-03-20
 * Time: 09:03
 */
class BillmateCheckoutBillmatecheckoutModuleFrontController extends ModuleFrontController
{

    public function postProcess()
    {
        
    }

    public function initContent()
    {
        parent::initContent();

        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);
        
        $this->setTemplate('checkout.tpl');
    }
}