<?php

/**
 * Created by PhpStorm.
 * User: Boxedsolutions
 * Date: 2017-03-20
 * Time: 09:03
 */
require_once(_PS_MODULE_DIR_.'/billmategateway/library/Common.php');

class BillmateCheckoutBillmatecheckoutModuleFrontController extends ModuleFrontController
{

    public $method = null;
    public function postProcess()
    {
        // UPDATE CHECKOUT with data
    }

    public function initContent()
    {
        parent::initContent();

        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);
        
        $this->context->smarty->assign('billmatecheckouturl',$this->getCheckout());
        
        $this->setTemplate('checkout.tpl');
    }

    public function getCheckout()
    {
        $billmate = $this->getBillmate();
        if($hash = $this->context->cookie->getBillmateHash()){
            $result = $billmate->getCheckout(array('PaymentData' => array('hash' => $hash)));
            if(!isset($result['code'])){
                $updateResult = $this->updateCheckout($result);
                if(!isset($updateResult['code'])){
                    $result = $billmate->getCheckout(array('PaymentData' => array('hash' => $hash)));
                    return $result['PaymentData']['url'];
                }
            }
        } else {
            $result = $this->initCheckout();
            if(!isset($result['code'])){
                return $result['url'];
            }
        }
    }

    public function getBillmate()
    {
        $eid    = Configuration::get('BILLMATE_ID');
        $secret = Configuration::get('BILLMATE_SECRET');
        $testMode = Configuration::get('BILLMATE_CHECKOUT_TESTMODE');
        return Common::getBillmate($eid,$secret,$testMode);
    }

    public function initCheckout()
    {
        $billmate = $this->getBillmate();
        $orderValues = array();
        // TODO all articles and stuff.
        $orderValues = $this->prepareCheckout();
        $orderValues['Articles'] = $this->prepareArticles();
        $discounts = $this->prepareDiscounts();
        if (count($discounts) > 0)
        {
            foreach ($discounts as $discount)
                array_push($orderValues['Articles'], $discount);
        }

        $orderValues['Cart'] = $this->prepareTotals();

        $result = $billmate->initCheckout($orderValues);
        if(!isset($result['code'])){
            $url = $result['url'];
            $parts = explode('/',$url);
            $sum = count($parts);
            $hash = ($parts[$sum-1] == 'test') ? str_replace('\\','',$parts[$sum-2]) : str_replace('\\','',$parts[$sum-1]);
            $this->context->cookie->setBillmateHash($hash);
        }
        return $result;
    }

    public function updateCheckout($values)
    {
        $billmate = $this->getBillmate();
        $orderValues = $values;
        $previousTotal = $orderValues['Cart']['Total']['withtax'];

        unset($orderValues['Cart']);
        unset($orderValues['Articles']);
        if($values['PaymentData']['method'] == 8 || $values['PaymentData']['method'] == 16){
            $payment_data['Card'] = array(
                'accepturl'    => $this->context->link->getModuleLink('billmategateway', 'accept', array('method' => $this->method),true),
                'cancelurl'    => $this->context->link->getModuleLink('billmategateway', 'cancel', array('method' => $this->method),true),
                'callbackurl'  => $this->context->link->getModuleLink('billmategateway', 'callback', array('method' => $this->method),true),
                'returnmethod' => (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == "on") ?'POST' : 'GET'
            );
        }
        $orderValues['Articles'] = $this->prepareArticles();
        $discounts = $this->prepareDiscounts();
        if (count($discounts) > 0)
        {
            foreach ($discounts as $discount)
                array_push($orderValues['Articles'], $discount);
        }
        $orderValues['Cart'] = $this->prepareTotals();


        $result = array();
        $result = $billmate->updateCheckout($orderValues);
        if($previousTotal != $orderValues['Cart']['Total']['withtax']){
            $result['update_checkout'] = true;
        } else {
            $result['update_checkout'] = false;

        }
        return $result;
    }

    public function prepareArticles()
    {
        $articles_arr = array();
        $articles    = $this->context->cart->getProducts();
        foreach ($articles as $article)
        {
            $taxrate       = ($article['price_wt'] == $article['price']) ? 0 : $article['rate'];

            $roundedArticle = round($article['price'], 2);
            $totalArticle = ($roundedArticle * $article['cart_quantity']) * 100;
            $articles_arr[] = array(
                'quantity'   => $article['cart_quantity'],
                'title'      => (isset($article['attributes']) && !empty($article['attributes'])) ? $article['name'].  ' - '.$article['attributes'] : $article['name'],
                'artnr'      => $article['reference'],
                'aprice'     => $roundedArticle * 100,
                'taxrate'    => $taxrate,
                'discount'   => 0,
                'withouttax' => ($roundedArticle * $article['cart_quantity']) * 100

            );
            if (!isset($this->prepare_discount[$taxrate]))
                $this->prepare_discount[$taxrate] = $totalArticle;
            else
                $this->prepare_discount[$taxrate] += $totalArticle;

            $this->totals += $totalArticle;
            $this->tax += round($totalArticle * ($taxrate / 100));

        }

        return $articles_arr;
    }
    public function prepareDiscounts()
    {
        $details = $this->context->cart->getSummaryDetails(null, true);
        $cartRules = $this->context->cart->getCartRules();
        $title = '';
        if (count($cartRules) > 0)
        {
            foreach ($cartRules as $cartRule)
            {
                $title .= $cartRule['name'].' ';
            }
        }
        $totalTemp = $this->totals;
        $discounts = array();
        if (!empty($details['total_discounts']))
        {
            foreach ($this->prepare_discount as $key => $value)
            {

                $percent_discount = $value / ($totalTemp);

                $discount_value = $percent_discount * $details['total_discounts'];
                $discount_amount = round($discount_value / (1 + ($key / 100)),2);

                $discounts[]    = array(
                    'quantity'   => 1,
                    'artnr'      => 'discount-'.$key,
                    'title'      => $title.sprintf($this->coremodule->l('Discount %s%% VAT'), $key),
                    'aprice'     => -($discount_amount * 100),
                    'taxrate'    => $key,
                    'discount'   => 0,
                    'withouttax' => -$discount_amount * 100
                );

                $this->totals -= $discount_amount * 100;
                $this->tax -= $discount_amount * ($key / 100) * 100;

            }

        }
        if (!empty($details['gift_products']))
        {
            foreach ($details['gift_products'] as $gift)
            {
                $discount_amount = 0;
                $taxrate        = 0;
                foreach ($this->context->cart->getProducts() as $product)
                {
                    $taxrate        = ($product['price_wt'] == $product['price']) ? 0 : $product['rate'];
                    $discount_amount = $product['price'];
                }
                $price          = $gift['price'] / $gift['cart_quantity'];
                $discount_amount = round($discount_amount / $gift['cart_quantity'],2);
                $total          = -($discount_amount * $gift['cart_quantity'] * 100);
                $discounts[]    = array(
                    'quantity'   => $gift['cart_quantity'],
                    'artnr'      => $this->coremodule->l('Discount'),
                    'title'      => $gift['name'],
                    'aprice'     => $price - round($discount_amount * 100, 0),
                    'taxrate'    => $taxrate,
                    'discount'   => 0,
                    'withouttax' => $total
                );

                $this->totals += $total;
                $this->tax += $total * ($taxrate / 100);
            }
        }

        return $discounts;
    }
    /**
     * Returns the Cart Object with Totals for Handling, Shipping and Total
     * @return array
     */
    public function prepareTotals()
    {
        $totals     = array();
        $details    = $this->context->cart->getSummaryDetails(null, true);

        $carrier    = $details['carrier'];
        $order_total = $this->context->cart->getOrderTotal();
        $notfree    = !(isset($details['free_ship']) && $details['free_ship'] == 1);

        if ($carrier->active && $notfree)
        {
            $carrier_obj = new Carrier($this->context->cart->id_carrier, $this->context->cart->id_lang);
            $taxrate    = $carrier_obj->getTaxesRate(new Address($this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));

            $total_shipping_cost  = round($this->context->cart->getTotalShippingCost(null, false),2);
            $totals['Shipping'] = array(
                'withouttax' => $total_shipping_cost * 100,
                'taxrate'    => $taxrate
            );
            $this->totals += $total_shipping_cost * 100;
            $this->tax += ($total_shipping_cost * ($taxrate / 100)) * 100;
        }
        if (Configuration::get('BINVOICE_FEE') > 0 && $this->method == 'invoice')
        {
            $fee           = Configuration::get('BINVOICE_FEE');
            $invoice_fee_tax = Configuration::get('BINVOICE_FEE_TAX');

            $tax                = new Tax($invoice_fee_tax);
            $tax_calculator      = new TaxCalculator(array($tax));
            $tax_rate            = $tax_calculator->getTotalRate();
            $fee = Tools::convertPriceFull($fee,null,$this->context->currency);
            $fee = round($fee,2);
            $totals['Handling'] = array(
                'withouttax' => $fee * 100,
                'taxrate'    => $tax_rate
            );
            $this->handling_fee = $fee;
            $this->handling_taxrate = $tax_rate;
            $order_total += $fee * (1 + ($tax_rate / 100));
            $this->totals += $fee * 100;
            $this->tax += (($tax_rate / 100) * $fee) * 100;
        }
        if (Configuration::get('BINVOICESERVICE_FEE') > 0 && $this->method == 'invoiceservice')
        {
            $fee           = Configuration::get('BINVOICESERVICE_FEE');
            $invoice_fee_tax = Configuration::get('BINVOICESERVICE_FEE_TAX');

            $tax                = new Tax($invoice_fee_tax);
            $tax_calculator      = new TaxCalculator(array($tax));
            $tax_rate            = $tax_calculator->getTotalRate();
            $fee = Tools::convertPriceFull($fee,null,$this->context->currency);
            $fee = round($fee,2);
            $totals['Handling'] = array(
                'withouttax' => $fee * 100,
                'taxrate'    => $tax_rate
            );
            $this->handling_fee = $fee;
            $this->handling_taxrate = $tax_rate;
            $order_total += $fee * (1 + ($tax_rate / 100));
            $this->totals += $fee * 100;
            $this->tax += (($tax_rate / 100) * $fee) * 100;
        }

        $rounding         = round($order_total * 100) - round($this->tax + $this->totals);
        $totals['Total']  = array(
            'withouttax' => round($this->totals),
            'tax'        => round($this->tax),
            'rounding'   => round($rounding),
            'withtax'    => round($this->totals + $this->tax + $rounding)
        );
        $this->paid_amount = $totals['Total']['withtax'];

        return $totals;
    }

    /**
     * A method for checkoutPreparation
     * @return array Billmate Request
     */
    public function prepareCheckout($method = null)
    {
        $payment_data                = array();

        $payment_data['PaymentData'] = array(
            'method'        => 93,
            'currency'      => Tools::strtoupper($this->context->currency->iso_code),
            'language'      => Tools::strtolower($this->context->language->iso_code),
            'country'       => Tools::strtoupper($this->context->country->iso_code),
            'orderid'       => Tools::substr($this->context->cart->id.'-'.time(), 0, 10),
            'logo' 			=> (Configuration::get('BILLMATE_LOGO')) ? Configuration::get('BILLMATE_LOGO') : ''

        );

        return $payment_data;

    }
}