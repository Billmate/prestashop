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
            $this->logEvent('Resolver failed to verify method');

            return false;
        }

        if (!$this->module = $this->getPaymentModule()) {
            $this->logEvent('Resolver failed to verify module');

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

    private function resolve($fileName, $className)
    {
        $filePath = str_replace('//', '/', sprintf('%s/billmategateway/methods/%s', _PS_MODULE_DIR_, $fileName));

        if (!file_exists($filePath)) {
            $this->logEvent('Resolver failed to locate class file');

            return null;
        }

        if (class_exists($className)) {
            $this->logEvent('Resolver failed because class already loaded');

            return null;
        }

        try {
            include_once($filePath);
        } catch (Exception $e) {
            $this->logEvent('Resolver failed load class file: '. $e->getMessage());

            return null;
        }

        return new $className;
    }

    private function logEvent($message)
    {
        try {
            PrestaShopLogger::addLog(sprintf('[BILLMATE] %s', $message), 1);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
