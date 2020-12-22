<?php

class BillmategatewayCancelModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>')) {
            $this->errors[] = $this->l('Betalningen avbröts på begäran av dig eller så uppstod det ett tekniskt fel.');
            $this->redirectWithNotifications('index.php?controller=order');
        }

        $this->errors[] = Tools::displayError('Betalningen avbröts på begäran av dig eller så uppstod det ett tekniskt fel.');
        Tools::redirect('index.php?controller=order');
    }
}
