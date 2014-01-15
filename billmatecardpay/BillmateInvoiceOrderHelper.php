<?php


class BillmateInvoiceOrderHelper //implements BillmateOrderHelper
{

    /**
     * @var Product
     */
    private $_cardpayfee;

    /**
     * @var PaymentModule
     */
    private $_module;

    /**
     * Cosntructor for BillmateInvoiceOrderHelper
     *
     * @param Product       $cardpayfee The cardpay fee product
     * @param PaymentModule $module     The cardpay payment module
     */
    public function __construct($cardpayfee, $module)
    {
        $this->_cardpayfee = $cardpayfee;
        $this->_module = $module;
    }

    /**
     * Get the order total from the cart
     *
     * @param Cart $cart The cart to calculate the order total from
     *
     * @return double
     */
    public function getOrderTotal($cart)
    {
        $total = $cart->getOrderTotal(true, Cart::BOTH);
        return $total + $this->_cardpayfee->getPrice();
    }

    /**
     * Authorize the purchase with the core module and the customers cart
     *
     * @param Module $core   The Billmate core module
     * @param Cart   $cart   The cart to build goodslist and addresses from
     * @param int    $pclass The pclass used for the purchase
     *
     * @return void
     */
    public function authorize($core, $cart, $pclass)
    {
        try {
            $this->_addInvoiceFee($cart);
            return $core->authorize($cart, -1);
        } catch (Exception $e) {
            $cart->deleteProduct($this->_cardpayfee->id);
            $cart->save();
            throw $e;
        }
    }

    /**
     * Validate the purchase order with the payment module and the information
     * from the checkout
     *
     * @param array $result The result array recieved from a addTransaction call
     * @param Cart  $cart   The cart to validate the order with
     *
     * @return void
     */
    public function validateOrder($result, $cart)
    {
        $total = $cart->getOrderTotal(true, Cart::BOTH);
        $state = BillmateCore::getOrderState($result["status"]);
        $customer = new Customer(intval($cart->id_customer));

        $this->_module->validateOrder(
            (int)$cart->id,
            (int)$state->id,
            $total,
            $this->_module->displayName,
            $this->_module->l('transaction') . " " . $result["ocr"],
            array(),
            null,
            false,
            $customer->secure_key
        );

        return $this->_module->currentOrder;
    }

    /**
     * Add the cardpay fee product to the cart
     *
     * @param Cart $cart The cart to add the cardpay fee to
     *
     * @return void
     */
    private function _addInvoiceFee($cart)
    {
        $this->_cardpayfee->quantity = 9001;
        $this->_cardpayfee->save();

        if ($this->_cardpayfee->getPrice() == 0) {
            return;
        }
        if ($cart->containsProduct($this->_cardpayfee->id, 0, 0)) {
            return;
        }
        if ($cart->updateQty(1, $this->_cardpayfee->id) == false) {
            throw new Exception("Failed to add cardpay fee");
        }
    }

    /**
     * Get the checkout template to show for the order
     *
     * @param KiTT_Locale $locale The locale to fetch the template for
     *
     * @return KiTT_Template
     */
    public function getTemplate($locale)
    {
        return KiTT::templateLoader($locale)->load("cardpay.html");
    }

    /**
     * Get the data to show in the checkout template
     *
     * @param KiTT_Locale $locale The locale to fetch the data for
     *
     * @return KiTT_TemplateData
     */
    public function getTemplateData($locale)
    {
        $templateData = KiTT::templateData(KiTT::INVOICE, $locale, null);
        $title = $this->getTitle($locale);
        $templateData->cardpay_fee_notice = $title["extra"];
        $templateData->cardpay_fee = $this->_cardpayfee->getPrice(true, null, 2);
        return $templateData;
    }

    /**
     * Get the title to show for the payment method in the checkout
     *
     * @param KiTT_Locale $locale The locale to fetch the title for
     *
     * @return KiTT_Title
     */
    public function getTitle($locale)
    {
        $KiTT_Title = KiTT::titleForInvoice(
            $locale,
            array(
                "fee" => $this->_cardpayfee->getPrice(true, null, 2),
                "feepos" => "extra",
                "feeformat" => "long"
            )
        );
        return $KiTT_Title->getTitle();
    }

    /**
     * Get the uri to the payment method
     *
     * @return string
     */
    public function getURI()
    {
        return __PS_BASE_URI__ . 'modules/billmatecardpay/';
    }

    /**
     * Get the module id
     *
     * @return int
     */
    public function getModuleId()
    {
        return $this->_module->id;
    }

}
