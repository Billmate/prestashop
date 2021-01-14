<?php

class Resolver
{
    private $method;
    private $module;

    public function __construct()
    {
        $this->method = Tools::getValue('method');
    }

    public function verify()
    {
        if (!in_array($this->method, $this->getValidMethods())) {
            // @todo: log...
            return false;
        }

        if (!$this->module = $this->getPaymentModule()) {
            // @todo: log...
            return false;
        }

        return true;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getModule()
    {
        return $this->module;
    }

    public function getInvoiceMethod()
    {
        return $this->resolve('Invoice.php', BillmateMethodInvoice::class);
    }

    private function getValidMethods()
    {
        return [
            'bankpay',
            'cardpay',
            'checkout',
            'invoice',
            'partpay',
        ];
    }

    private function getPaymentModule()
    {
        switch ($this->method) {
            case 'bankpay':
                return $this->resolve('Bankpay.php', BillmateMethodBankpay::class);

            case 'cardpay':
                return $this->resolve('Cardpay.php', BillmateMethodCardpay::class);

            case 'checkout':
                return $this->resolve('Checkout.php', BillmateMethodCheckout::class);

            case 'invoice':
                return $this->resolve('Invoice.php', BillmateMethodInvoice::class);

            case 'partpay':
                return $this->resolve('Partpay.php', BillmateMethodPartpay::class);

            default:
                return null;
        }
    }

    private function resolve(string $fileName, string $className)
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

        try {
            include_once($filePath);
        } catch (Exception $e) {
            // @todo: log...
            return null;
        }

        return new $className;
    }
}
