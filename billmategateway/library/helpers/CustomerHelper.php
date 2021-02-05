<?php

class CustomerHelper
{
    public function createCustomer($customerId)
    {
        if (Customer::customerIdExistsStatic($customerId)) {
            $customer = new Customer($customerId);
        } else {
            $customer = new Customer();
            $customer->firstname = 'NoName';
            $customer->lastname = 'NoName';
            $customer->email = 'missing@email.com';

            $customer->newsletter = 0;
            $customer->optin = 0;
            $customer->is_guest = 1;
            $customer->active = 1;
            $customer->passwd = Tools::encrypt(Tools::passwdGen(8));

            $customer->add();
        }

        return $customer;
    }

    public function updateCustomer(Customer $customer, array $data = [])
    {
        $data = array_map('utf8_decode', $data['Billing']);

        $customer->firstname = !empty($data['firstname']) ? $data['firstname'] : '';
        $customer->lastname = !empty($data['lastname']) ? $data['lastname'] : '';
        $customer->company = !empty($data['company']) ? $data['company'] : '';
        $customer->email = !empty($data['email']) ? $data['email'] : '';
        $customer->phone = !empty($data['phone']) ? $data['phone'] : '';

        try {
            $customer->update();
        } catch (Exception $e) {
            $this->logEvent('Failed to update customer in helper: '. $e->getMessage());

            return null;
        }

        return $customer;
    }

    public function createBillingAddress(Customer $customer, array $data = [])
    {
        $data = array_map('utf8_decode', $data['Billing']);

        return $this->createAddress($customer, $data, 'Billing');
    }

    public function createShippingAddress(Customer $customer, array $data = [])
    {
        $data = !empty($data['Shipping']) ? $data['Shipping'] : $data['Billing'];
        $data = array_map('utf8_decode', $data);

        return $this->createAddress($customer, $data, 'Shipping');
    }

    public function createAddress(Customer $customer, array $data = [], $alias = null)
    {
        $address = new Address();

        $address->id_customer = $customer->id;
        $address->alias = !is_null($alias) ? $alias : 'Billing';

        $address->firstname = !empty($data['firstname']) ? $data['firstname'] : '';
        $address->lastname = !empty($data['lastname']) ? $data['lastname'] : '';
        $address->company = !empty($data['company']) ? $data['company'] : '';
        $address->phone = !empty($data['phone']) ? $data['phone'] : '';
        $address->phone_mobile = !empty($data['phone']) ? $data['phone'] : '';
        $address->address1 = !empty($data['street']) ? $data['street'] : '';
        $address->postcode = !empty($data['zip']) ? $data['zip'] : '';
        $address->city = !empty($data['city']) ? $data['city'] : '';
        $address->country = !empty($data['country']) ? $data['country'] : '';

        if ($address->country) {
            $address->id_country = Country::getByIso($address->country);
        }

        try {
            $address->save();
        } catch (Exception $e) {
            $this->logEvent('Failed to save address in helper: '. $e->getMessage());

            return null;
        }

        return $address;
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
