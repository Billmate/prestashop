<?php
class CustomerConfig
{
    const BM_CUSTOMER_ID_CONFIG = 'BM_CHECKOUT_CUSTOMER_ID';
    
    const BM_ADDRESS_ID_CONFIG = 'BM_CHECKOUT_ADDRESS_ID';

    const BM_CUSTOMER_FIRSTNAME = 'Billmate';

    const BM_CUSTOMER_LASTNAME = 'Checkout';

    const BM_CUSTOMER_EMAIL = 'billmate.default@billmate.se';

    /**
     * @var ContextCore
     */
    protected $context;

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    /**
     * @return bool
     */
    protected function createDefaultCustomer()
    {
        $customerId = $this->createCustomer();
        $this->addDefaultAddress($customerId);
        return $this->setConfigValue($customerId);
    }

    /**
     * @return CustomerCore
     */
    public function getDefaultCustomer()
    {
        $customerId = $this->getCustomerId();
        if (!$customerId) {
            $this->createDefaultCustomer();
            $customerId = $this->getCustomerId();
        }

        return new Customer($customerId);
    }

        /**
     * @return int
     */
    protected function createCustomer()
    {
        $customerObject = new Customer();
        $customerObject->firstname = self::BM_CUSTOMER_FIRSTNAME;
        $customerObject->lastname  = self::BM_CUSTOMER_LASTNAME;
        $customerObject->company   = '';
        $customerObject->passwd = $this->generatePassword();
        $customerObject->id_default_group = $this->getDefaultGroupId();
        $customerObject->email = 'billmate.default@billmate.se';
        $customerObject->active = true;
        $customerObject->is_guest = true;
        $customerObject->add();
        return $customerObject->id;
    }

    public function addDefaultAddress($customerId)
    {
        $addressId = $this->getDefaultAddressId();
        if (!Customer::customerHasAddress($customerId, $addressId)) {
            $defaultAddress = new Address();
            $defaultAddress->id_customer = (int)$customerId;
            $defaultAddress->firstname = self::BM_CUSTOMER_FIRSTNAME;
            $defaultAddress->lastname  = self::BM_CUSTOMER_LASTNAME;
            $defaultAddress->company   = '';
            $defaultAddress->phone = '0712345678';
            $defaultAddress->phone_mobile = '0712345678';
            $defaultAddress->address1 = 'Testgatan 1	';
            $defaultAddress->postcode = '12345';
            $defaultAddress->city     = 'Teststad';
            $defaultAddress->country  = 'SE';
            $defaultAddress->alias    = 'DefAddress-'.date('Y-m-d');
            $defaultAddress->id_country = Country::getByIso('SE');
            $defaultAddress->save();
            $this->setConfigAddressValue($defaultAddress->id);
        }

        return $this;
    }

    /**
     * @param $customerId
     *
     * @return mixed
     */
    protected function setConfigValue($customerId)
    {
        return Configuration::updateValue(self::BM_CUSTOMER_ID_CONFIG, $customerId);
    }

    /**
     * @param $addressId
     *
     * @return mixed
     */
    protected function setConfigAddressValue($addressId)
    {
        return Configuration::updateValue(self::BM_ADDRESS_ID_CONFIG, $addressId);
    }

    /**
     * @return int
     */
    protected function getDefaultAddressId()
    {
        return Configuration::get(self::BM_ADDRESS_ID_CONFIG);
    }

    /**
     * @return int
     */
    protected function getDefaultGroupId()
    {
        return (int)(Configuration::get('PS_CUSTOMER_GROUP'));
    }

    /**
     * @return int
     */
    protected function getCustomerId()
    {
        return (int)(Configuration::get(self::BM_CUSTOMER_ID_CONFIG));
    }

    /**
     * @return bool|string
     */
    protected function generatePassword()
    {
        return Tools::passwdGen(8);
    }
}
