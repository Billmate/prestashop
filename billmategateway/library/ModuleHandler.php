<?php

class ModuleHandler
{
    public function __construct()
    {
        $this->method = Tools::getValue('method');
    }

    private function initPaymentModule(string $fileName, string $className)
    {
        $filePath = str_replace('//', '/', sprintf('%s/billmategateway/methods/%s', _PS_MODULE_DIR_, $fileName));

        if (!file_exists($filePath)) {
            // @todo: log...
            return null;
        }

        if (class_exists($className)) {
            // @todo: log...
            return null;
        }

        include_once($filePath);

        return new $className;
    }

    public function getPaymentModule()
    {
        switch ($this->method) {
            case 'bankpay':
                return $this->initPaymentModule('Bankpay.php', BillmateMethodBankpay::class);

            case 'cardpay':
                return $this->initPaymentModule('Cardpay.php', BillmateMethodCardpay::class);

            case 'checkout':
                return $this->initPaymentModule('Checkout.php', BillmateMethodCheckout::class);

            case 'invoice':
                return $this->initPaymentModule('Invoice.php', BillmateMethodInvoice::class);

            case 'partpay':
                return $this->initPaymentModule('Partpay.php', BillmateMethodPartpay::class);

            default:
                return null;
        }
    }
}
