<?php

require_once(_PS_MODULE_DIR_ . 'billmategateway/library/abstracts/CallbackController.php');

class BillmategatewayAcceptModuleFrontController extends CallbackController
{
    public $request = 'accept';

    public function __construct()
    {
        parent::__construct();
    }

    public function postProcess()
    {
        if ($this->method == 'checkout') {
            $checkoutOrderId = Common::getCartCheckoutHash();

            if ($checkoutOrderId) {
                return $this->respondWithSuccess(array(
                    'hash' => urlencode($checkoutOrderId),
                ));
            }
        }

        // If payment data is invalid, show default confirm page
        if (!$this->client->verifyPaymentData()) {
            return $this->respondWithSuccess(true);
        }

        // If payment is not payed, show default confirm page
        if (!$this->client->isPayed()) {
            return $this->respondWithSuccess(true);
        }

        // If no order id is set, show default confirm page
        if (!$orderId = $this->client->getOrderId()) {
            return $this->respondWithSuccess(true);
        }

        // If no order is found, show default confirm page
        if (!$order = $this->orderHelper->getOrderByReference($orderId)) {
            return $this->respondWithSuccess(true);
        }

        // Show confirm page with order data
        return $this->respondWithSuccess(array(
            'oid' => $order->id,
        ));
    }

    protected function respondWithError($value)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>')) {
            $this->errors[] = $this->l('Det uppstod ett problem, var vänlig försök igen eller prova ett annat betalsätt.');
            $this->redirectWithNotifications('index.php?controller=order');
        }

        $this->errors[] = Tools::displayError('Det uppstod ett problem, var vänlig försök igen eller prova ett annat betalsätt.');
        Tools::redirect('index.php?controller=order');
    }

    protected function respondWithSuccess($value)
    {
        $query = (!empty($value) && is_array($value)) ? $value : array();

        Tools::redirect(
            $this->context->link->getModuleLink('billmategateway', 'confirm', $query, true)
        );
    }
}
