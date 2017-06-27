<?php

/**
 * Created by PhpStorm.
 * User: Boxedsolutions
 * Date: 2017-03-20
 * Time: 09:03
 */
require_once(_PS_MODULE_DIR_.'/billmategateway/library/Common.php');

ini_set('display_errors',1);
class BillmateCheckoutBillmatecheckoutModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;
    public $display_column_right = false;
    public $ssl = true;


    /** @var int $paid_amount for use with Billmate Invoice to sett correct amount */
    protected $paid_amount = 0;
    public $totals;
    public $tax;
    public $method = 'invoice';
    public function postProcess()
    {
        // UPDATE CHECKOUT with data
        if( $this->ajax = Tools::getValue( "ajax" ) && Tools::getValue('action') == 'setShipping') {
            if (Tools::getIsset('delivery_option')) {
                $validated = false;
                try {
                    if ($this->validateDeliveryOption(Tools::getValue('delivery_option'))) {
                        $validated = true;
                        $this->context->cart->setDeliveryOption(Tools::getValue('delivery_option'));
                    }
                    $updated = false;
                    if (!$this->context->cart->update()) {
                        $updated = true;
                        $this->context->smarty->assign(array(
                            'vouchererrors' => Tools::displayError('Could not save carrier selection'),
                        ));
                    }
                    $this->context->cart->save();

                    // Carrier has changed, so we check if the cart rules still apply
                    CartRule::autoRemoveFromCart($this->context);
                    CartRule::autoAddToCart($this->context);
                    $values = $this->fetchCheckout();
                    $result = $this->updateCheckout($values);
                    $result['validatedDelivery'] = $validated;
                    $result['updated'] = $updated;
                    $result['success'] = true;
                    echo Tools::jsonEncode($result);
                    die;
                } catch(Exception $e){
                    $result['success'] = false;
                    $result['message'] = $e->getMessage();
                    $result['trace'] = $e;
                    echo Tools::jsonEncode($result);
                    die;
                }

            }
        }
        // Cart contents is changed from the dropdown
        if($this->ajax = Tools::getValue('ajax') && Tools::getValue('action') == 'updateCheckout'){
            if($this->context->cart->nbProducts() == 0){
                echo Tools::jsonEncode(array('success' => false));
                die();
            }
            $values = $this->fetchCheckout();
            $result = $this->updateCheckout($values);
            $result['success'] = true;
            echo Tools::jsonEncode($result);
            die;
            
        }
        if( $this->ajax = Tools::getValue( "ajax" ) && Tools::getValue('action') == 'setAddress') {
            $result = $this->fetchCheckout();
            $customer = $result['Customer'];
            $address = $customer['Billing'];
            $country = isset($customer['Billing']['country']) ? $customer['Billing']['country'] : 'SE';
            $bill_phone = isset($customer['Billing']['phone']) ? $customer['Billing']['phone'] : '';
            $logfile   = _PS_CACHE_DIR_.'Billmate.log';

            file_put_contents($logfile, 'customer:'.print_r($this->context->customer,true),FILE_APPEND);
            file_put_contents($logfile, 'cart:'.print_r($this->context->cart,true),FILE_APPEND);
            if($this->context->cart->id_customer == 0){
                // Create a guest customer
                $customerObject = new Customer();
                $password = Tools::passwdGen(8);
                $customerObject->firstname = !empty($address['firstname']) ? $address['firstname'] : '';
                $customerObject->lastname  = !empty($address['lastname']) ? $address['lastname'] : '';
                $customerObject->company   = isset($address['company']) ? $address['company'] : '';
                $customerObject->passwd = $password;
                $customerObject->id_default_group = (int) (Configuration::get('PS_CUSTOMER_GROUP', null, $this->context->cart->id_shop));

                $customerObject->email = $address['email'];
                $customerObject->active = true;
                $customerObject->is_guest = true;
                $customerObject->add();
                $this->context->customer = $customerObject;
                $this->context->cart->secure_key = $customerObject->secure_key;
                $this->context->cart->id_customer = $customerObject->id;
            }
            file_put_contents($logfile, 'customer after:'.print_r($this->context->customer,true),FILE_APPEND);
            file_put_contents($logfile, 'cart after:'.print_r($this->context->cart,true),FILE_APPEND);

            $customer_addresses = $this->context->customer->getAddresses($this->context->language->id);

            if (count($customer_addresses) == 1)
                $customer_addresses[] = $customer_addresses;

            $matched_address_id = false;
            foreach ($customer_addresses as $customer_address)
            {
                if (isset($customer_address['address1']))
                {
                    $billing  = new Address($customer_address['id_address']);

                    $user_bill = $billing->firstname.' '.$billing->lastname.' '.$billing->company;
                    $company = isset($address['company']) ? $address['company'] : '';
                    $api_name = $address['firstname']. ' '. $address['lastname'].' '.$company;

                    if (Common::matchstr($user_bill,$api_name) && Common::matchstr($customer_address['address1'], $address['street']) &&
                        Common::matchstr($customer_address['postcode'], $address['zip']) &&
                        Common::matchstr($customer_address['city'], $address['city']) &&
                        Common::matchstr(Country::getIsoById($customer_address['id_country']), $address['country']))

                        $matched_address_id = $customer_address['id_address'];
                }
                else
                {
                    foreach ($customer_address as $c_address)
                    {
                        $billing  = new Address($c_address['id_address']);

                        $user_bill = $billing->firstname.' '.$billing->lastname.' '.$billing->company;
                        $company = isset($address['company']) ? $address['company'] : '';
                        $api_name = $address['firstname']. ' '. $address['lastname'].' '.$company;


                        if (Common::matchstr($user_bill,$api_name) &&  Common::matchstr($c_address['address1'], $address['street']) &&
                            Common::matchstr($c_address['postcode'], $address['zip']) &&
                            Common::matchstr($c_address['city'], $address['city']) &&
                            Common::matchstr(Country::getIsoById($c_address['id_country']), $address['country'])
                        )
                            $matched_address_id = $c_address['id_address'];
                    }
                }

            }
            if (!$matched_address_id)
            {
                $addressnew              = new Address();
                $addressnew->id_customer = (int)$this->context->customer->id;

                $addressnew->firstname = !empty($address['firstname']) ? $address['firstname'] : $billing->firstname;
                $addressnew->lastname  = !empty($address['lastname']) ? $address['lastname'] : $billing->lastname;
                $addressnew->company   = isset($address['company']) ? $address['company'] : '';

                $addressnew->phone        = $address['phone'];
                $addressnew->phone_mobile = $address['phone'];

                $addressnew->address1 = $address['street'];
                $addressnew->postcode = $address['zip'];
                $addressnew->city     = $address['city'];
                $addressnew->country  = $address['country'];
                $addressnew->alias    = 'Bimport-'.date('Y-m-d');
                $addressnew->id_country = Country::getByIso($address['country']);
                $addressnew->save();

                $matched_address_id = $addressnew->id;
            }


            $billing_address_id = $shipping_address_id = $matched_address_id;

            if(isset($customer['Shipping']) && count($customer['Shipping']) > 0){
                $address = $customer['Shipping'];
                file_put_contents($logfile, 'shippingAddress:'.print_r($address,true),FILE_APPEND);
                file_put_contents($logfile, 'customerAddress:'.print_r($customer_addresses,true),FILE_APPEND);

                $matched_address_id = false;
                foreach ($customer_addresses as $customer_address)
                {
                    if (isset($customer_address['address1']))
                    {
                        $billing  = new Address($customer_address['id_address']);

                        $user_bill = $billing->firstname.' '.$billing->lastname.' '.$billing->company;
                        $company = isset($address['company']) ? $address['company'] : '';
                        $api_name = $address['firstname']. ' '. $address['lastname'].' '.$company;

                        if (Common::matchstr($user_bill,$api_name) && Common::matchstr($customer_address['address1'], $address['street']) &&
                            Common::matchstr($customer_address['postcode'], $address['zip']) &&
                            Common::matchstr($customer_address['city'], $address['city']) &&
                            Common::matchstr(Country::getIsoById($customer_address['id_country']), $address['country']))

                            $matched_address_id = $customer_address['id_address'];
                    }
                    else
                    {
                        foreach ($customer_address as $c_address)
                        {
                            $billing  = new Address($c_address['id_address']);

                            $user_bill = $billing->firstname.' '.$billing->lastname.' '.$billing->company;
                            $company = isset($address['company']) ? $address['company'] : '';
                            $api_name = $address['firstname']. ' '. $address['lastname'].' '.$company;


                            if (Common::matchstr($user_bill,$api_name) &&  Common::matchstr($c_address['address1'], $address['street']) &&
                                Common::matchstr($c_address['postcode'], $address['zip']) &&
                                Common::matchstr($c_address['city'], $address['city']) &&
                                Common::matchstr(Country::getIsoById($c_address['id_country']), $address['country'])
                            )
                                $matched_address_id = $c_address['id_address'];
                        }
                    }

                }
                if(!$matched_address_id) {
                    $address = $customer['Shipping'];
                    $addressshipping = new Address();
                    $addressshipping->id_customer = (int)$this->context->customer->id;

                    $addressshipping->firstname = !empty($address['firstname']) ? $address['firstname'] : '';
                    $addressshipping->lastname = !empty($address['lastname']) ? $address['lastname'] : '';
                    $addressshipping->company = isset($address['company']) ? $address['company'] : '';

                    $addressshipping->phone = isset($address['phone']) ? $address['phone'] : $bill_phone;
                    $addressshipping->phone_mobile = isset($address['phone']) ? $address['phone'] : $bill_phone;

                    $addressshipping->address1 = $address['street'];
                    $addressshipping->postcode = $address['zip'];
                    $addressshipping->city = $address['city'];
                    $addressshipping->country = isset($address['country']) ? $address['country'] : $country;
                    $addressshipping->alias = 'Bimport-' . date('Y-m-d');
                    $addressshipping->id_country = Country::getByIso(isset($address['country']) ? $address['country'] : $country);
                    $addressshipping->save();

                    $shipping_address_id = $addressshipping->id;
                } else {
                    $shipping_address_id = $matched_address_id;
                }
            }

            $this->context->cart->id_address_invoice  = (int)$billing_address_id;
            $this->context->cart->id_address_delivery = (int)$shipping_address_id;


            $carrier = new Carrier($this->context->cart->id_carrier,$this->context->cart->id_lang);
            $delivery_option = $this->context->cart->getDeliveryOption();
            $delivery_option[(int)$this->context->cart->id_address_delivery] = $this->context->cart->id_carrier.',';
            $this->context->cart->setDeliveryOption($delivery_option);

            $this->context->cart->update();
            $this->context->cart->save();
            CartRule::autoRemoveFromCart($this->context);
            CartRule::autoAddToCart($this->context);
            $carrierBlock = $this->_getCarrierList();



            $response['success'] = true;
            $response['carrier_block'] = $carrierBlock['carrier_block'];
            echo Tools::jsonEncode($response);
            die;
        }
        if( $this->ajax = Tools::getValue( "ajax" ) && Tools::getValue('action') == 'setPaymentMethod'){
            $checkout = $this->fetchCheckout();
            if(!isset($checkout['code'])){
                switch($checkout['PaymentData']['method']){
                    case '4':
                        $this->method = 'partpay';
                        break;
                    case '8':
                        $this->method = 'cardpay';
                        break;
                    case '16':
                        $this->method = 'bankpay';
                        break;
                    default:
                        $this->method = 'invoice';
                        break;

                }
            }
            $checkout['debug'] = 'setPaymentMethod';
            $result = $this->updateCheckout($checkout);
            echo Tools::jsonEncode(array('success' => true));
            die;
        }
        if( $this->ajax = Tools::getValue( "ajax" ) && Tools::getValue('action') == 'validateOrder') {
            $checkout = $this->fetchCheckout();
            $this->ajax = true;
            $result = $this->sendResponse($checkout);
            
            echo Tools::jsonEncode($result);
            die();
        
        }

        }

    public function sendResponse($result)
    {
        $return = array();
        $billmate = $this->getBillmate();

        require_once(_PS_MODULE_DIR_.'billmategateway/methods/'.Tools::ucfirst($this->method).'.php');



        $class        = "BillmateMethod".Tools::ucfirst($this->method);
        $this->module = new $class;
        switch ($this->method) {
            case 'invoice':
            case 'partpay':
            case 'invoiceservice':
                if (!isset($result['code']) && (isset($result['PaymentData']['order']['number']) && is_numeric($result['PaymentData']['order']['number']) && $result['PaymentData']['order']['number'] > 0)) {

                    $status = ($this->method == 'invoice') ? Configuration::get('BINVOICE_ORDER_STATUS') : Configuration::get('BPARTPAY_ORDER_STATUS');
                    $status = ($this->method == 'invoiceservice') ? Configuration::get('BINVOICESERVICE_ORDER_STATUS') : $status;
                    $status = ($result['PaymentData']['order']['status'] == 'Pending') ? Configuration::get('BILLMATE_PAYMENT_PENDING') : $status;

                    if(Validate::isLoadedObject($this->context->cart) && $this->context->cart->OrderExists() == false) {
                        $extra = array('transaction_id' => $result['PaymentData']['order']['number']);
                        $total = $this->context->cart->getOrderTotal();
                        $total = $result['Cart']['Total']['withtax'];
                        $total = $total/100;
                        $customer = new Customer((int)$this->context->cart->id_customer);
                        $orderId = 0;
                        if ($this->method == 'partpay') {
                            $this->module->validateOrder((int)$this->context->cart->id,
                                $status,
                                $total,
                                $this->module->displayName,
                                null, $extra, null, false, $customer->secure_key);
                            $orderId = $this->module->currentOrder;
                        } else {
                            $this->module->validateOrder((int)$this->context->cart->id,
                                $status,
                                $total,
                                $this->module->displayName,
                                null, $extra, null, false, $customer->secure_key);
                            $orderId = $this->module->currentOrder;
                        }
                        $values = array();
                        $values['PaymentData'] = array(
                            'number' => $result['PaymentData']['order']['number'],
                            'orderid' => (Configuration::get('BILLMATE_SEND_REFERENCE') == 'reference') ? $this->module->currentOrderReference : $this->module->currentOrder
                        );

                        $billmate->updatePayment($values);
                    }

                    $url = 'order-confirmation&id_cart=' . (int)$this->context->cart->id .
                        '&id_module=' . (int)$this->getmoduleId('billmate' . $this->method) .
                        '&id_order=' . (int)$orderId .
                        '&key=' . $customer->secure_key;
                    $return['success'] = true;
                    $return['redirect'] = $this->context->link->getPageLink($url, true);
                    if (isset($this->context->cookie->billmatepno))
                        unset($this->context->cookie->billmatepno);
                } else {
                    if (in_array($result['code'], array(2401, 2402, 2403, 2404, 2405))) {
                        //$result = $this->checkAddress();

                        if (is_array($result))
                            die(Tools::jsonEncode($result));
                    }
                    //Logger::addLog($result['message'], 1, $result['code'], 'Cart', $this->context->cart->id);
                    $return = array('success' => false, 'content' => utf8_encode($result['message']));
                }

                break;
            case 'bankpay':
            case 'cardpay':
                if (!isset($result['code'])) {
                    unset($this->context->cookie->BillmateHash);
                    if ($this->ajax) {
                        $return = array('success' => true, 'redirect' => $result['url']);
                    } else {
                        header('Location: ' . $result['url']);
                    }
                }
                else {
                    //Logger::addLog($result['message'], 1, $result['code'], 'Cart', $this->context->cart->id);
                    $return = array('success' => false, 'content' => utf8_encode($result['message']));
                }


                break;
        }
        return $return;//die(Tools::JsonEncode($return));
    }

    public function initContent()
    {
        parent::initContent();
        if($this->context->cart->nbProducts() == 0){
            $this->setTemplate('checkout-empty.tpl');
        } else {
            CartRule::autoRemoveFromCart($this->context);
            CartRule::autoAddToCart($this->context);

            $this->context->smarty->assign('billmatecheckouturl',$this->getCheckout());


            //$this->context->smarty->assign('HOOK_LEFT_COLUMN', Module::hookExec('displayLeftColumn'));

            $carrierBlock = $this->_getCarrierList();
            $this->context->smarty->assign('carrier_block',$carrierBlock['carrier_block']);
            //Cart::addExtraCarriers($result);
            $this->setTemplate('checkout.tpl');
        }
    }

    protected function validateDeliveryOption($delivery_option)
    {
        if (!is_array($delivery_option)) {
            return false;
        }

        foreach ($delivery_option as $option) {
            if (!preg_match('/(\d+,)?\d+/', $option)) {
                return false;
            }
        }

        return true;
    }

    protected function _getCarrierList()
    {
        $address_delivery = new Address($this->context->cart->id_address_delivery);

        $cms = new CMS(Configuration::get('PS_CONDITIONS_CMS_ID'), $this->context->language->id);
        $link_conditions = $this->context->link->getCMSLink($cms, $cms->link_rewrite, Configuration::get('PS_SSL_ENABLED'));
        if (!strpos($link_conditions, '?')) {
            $link_conditions .= '?content_only=1';
        } else {
            $link_conditions .= '&content_only=1';
        }

        $carriers = $this->context->cart->simulateCarriersOutput();
        $delivery_option = $this->context->cart->getDeliveryOption(null, false, false);

        $wrapping_fees = $this->context->cart->getGiftWrappingPrice(false);
        $wrapping_fees_tax_inc = $this->context->cart->getGiftWrappingPrice();
        $old_message = Message::getMessageByCartId((int)$this->context->cart->id);

        $free_shipping = false;
        foreach ($this->context->cart->getCartRules() as $rule) {
            if ($rule['free_shipping'] && !$rule['carrier_restriction']) {
                $free_shipping = true;
                break;
            }
        }

        $this->context->smarty->assign('isVirtualCart', $this->context->cart->isVirtualCart());

        $vars = array(
            'advanced_payment_api' => (bool)Configuration::get('PS_ADVANCED_PAYMENT_API'),
            'free_shipping' => $free_shipping,
            'checkedTOS' => (int)$this->context->cookie->checkedTOS,
            'recyclablePackAllowed' => (int)Configuration::get('PS_RECYCLABLE_PACK'),
            'giftAllowed' => (int)Configuration::get('PS_GIFT_WRAPPING'),
            'cms_id' => (int)Configuration::get('PS_CONDITIONS_CMS_ID'),
            'conditions' => (int)Configuration::get('PS_CONDITIONS'),
            'link_conditions' => $link_conditions,
            'recyclable' => (int)$this->context->cart->recyclable,
            'gift_wrapping_price' => (float)$wrapping_fees,
            'total_wrapping_cost' => Tools::convertPrice($wrapping_fees_tax_inc, $this->context->currency),
            'total_wrapping_tax_exc_cost' => Tools::convertPrice($wrapping_fees, $this->context->currency),
            'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
            'carriers' => $carriers,
            'checked' => $this->context->cart->simulateCarrierSelectedOutput(),
            'delivery_option' => $delivery_option,
            'address_collection' => $this->context->cart->getAddressCollection(),
            'opc' => true,
            'oldMessage' => isset($old_message['message'])? $old_message['message'] : '',
            'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array(
                'carriers' => $carriers,
                'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
                'delivery_option' => $delivery_option
            ))
        );

        Cart::addExtraCarriers($vars);

        $this->context->smarty->assign($vars);

        if (!Address::isCountryActiveById((int)$this->context->cart->id_address_delivery) && $this->context->cart->id_address_delivery != 0) {
            $this->errors[] = Tools::displayError('This address is not in a valid area.');
        } elseif ((!Validate::isLoadedObject($address_delivery) || $address_delivery->deleted) && $this->context->cart->id_address_delivery != 0) {
            $this->errors[] = Tools::displayError('This address is invalid.');
        } else {
            $result = array(
                'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array(
                    'carriers' => $carriers,
                    'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
                    'delivery_option' => $this->context->cart->getDeliveryOption(null, true)
                )),
                'carrier_block' => $this->context->smarty->fetch(_PS_THEME_DIR_.'order-carrier.tpl')
            );

            Cart::addExtraCarriers($result);
            return $result;
        }
        if (count($this->errors)) {
            return array(
                'hasError' => true,
                'errors' => $this->errors,
                'carrier_block' => $this->context->smarty->fetch(_PS_THEME_DIR_.'order-carrier.tpl')
            );
        }
    }

    public function fetchCheckout()
    {
        $billmate = $this->getBillmate();

        if($hash = $this->context->cookie->__get('BillmateHash')){
            $result = $billmate->getCheckout(array('PaymentData' => array('hash' => $hash)));
            if(!isset($result['code'])){
                return $result;

            }
        }
    }

    public function getCheckout()
    {
        $billmate = $this->getBillmate();
        if($hash = $this->context->cookie->__get('BillmateHash')){
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
            $this->context->cookie->__set('BillmateHash',$hash);
        }
        return $result;
    }

    public function updateCheckout($values)
    {
        $billmate = $this->getBillmate();
        $this->totals = 0;
        $this->tax = 0;

        $orderValues = $values;
        $previousTotal = $orderValues['Cart']['Total']['withtax'];

        unset($orderValues['Cart']);
        unset($orderValues['Articles']);
        unset($orderValues['Customer']);
        unset($orderValues['PaymentData']['status']);
        $orderValues['PaymentData']['accepturl'] = $this->context->link->getModuleLink('billmategateway', 'accept', array('method' => $this->method),true);
        $orderValues['PaymentData']['cancelurl']    = $this->context->link->getModuleLink('billmategateway', 'cancel', array('method' => $this->method, 'type' => 'checkout'),true);
        $orderValues['PaymentData']['callbackurl']  = $this->context->link->getModuleLink('billmategateway', 'callback', array('method' => $this->method),true);
        $orderValues['PaymentData']['returnmethod'] = (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == "on") ?'POST' : 'GET';

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

        if(is_array($result)){
            if($previousTotal != $orderValues['Cart']['Total']['withtax']){
                $result['update_checkout'] = true;
            } else {
                $result['update_checkout'] = false;

            }
            return $result;
        } else {

            return array('code' => 9510, 'communication error, '.$result);
        }
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
                'withouttax' => ($roundedArticle * $article['cart_quantity']) * 100,
                'total_article' => $totalArticle

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
            if($taxrate > 0) {
                $this->tax += ($total_shipping_cost * ($taxrate / 100)) * 100;
            }
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
        $cms = new CMS(
            (int) (Configuration::get('PS_CONDITIONS_CMS_ID')),
            (int) ($this->context->cookie->id_lang)
        );

        $link_conditions = $this->context->link->getCMSLink($cms, $cms->link_rewrite, true);
        $termsPage = $link_conditions;
        $payment_data['PaymentData'] = array(
            'method'        => 93,
            'currency'      => Tools::strtoupper($this->context->currency->iso_code),
            'language'      => Tools::strtolower($this->context->language->iso_code),
            'country'       => Tools::strtoupper($this->context->country->iso_code),
            'orderid'       => Tools::substr($this->context->cart->id.'-'.time(), 0, 10),
            'logo' 			=> (Configuration::get('BILLMATE_LOGO')) ? Configuration::get('BILLMATE_LOGO') : '',
            'accepturl'    => $this->context->link->getModuleLink('billmategateway', 'accept', array('method' => $this->method),true),
            'cancelurl'    => $this->context->link->getModuleLink('billmategateway', 'cancel', array('method' => $this->method),true),
            'callbackurl'  => $this->context->link->getModuleLink('billmategateway', 'callback', array('method' => $this->method),true)

        );

        $payment_data['CheckoutData'] = array(
            'terms' => $termsPage,
            'windowmode' => 'iframe',
            'sendreciept' => 'yes',

        );

        return $payment_data;

    }

    public function getmoduleId($method)
    {
        $id2name = array();
        $sql = 'SELECT `id_module`, `name` FROM `'._DB_PREFIX_.'module`';
        if ($results = Db::getInstance()->executeS($sql)) {
            foreach ($results as $row) {
                $id2name[$row['name']] = $row['id_module'];
            }
        }
        return $id2name[$method];
    }
}