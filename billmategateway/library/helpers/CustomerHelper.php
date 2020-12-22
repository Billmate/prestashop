<?php

class CustomerHelper
{
    public function getOrCreateCustomer(Cart $cart)
    {
        try {
            if (Customer::customerIdExistsStatic($cart->id_customer)) {
                $customer = new Customer($cart->id_customer);
            } else {
                $customer = new Customer();
                $customer->firstname = 'NoName';
                $customer->lastname = 'NoName';
                $customer->email = 'missing@email.com';
                $customer->passwd = 'nopass';
                $customer->add();
            }
        } catch (Exception $e) {
            die($e->getMessage());
            return null;
        }

        return $customer;
    }

    public function updateCustomer(Customer $customer, array $data)
    {
        $data = $data['Billing'];

        $customer->firstname = !empty($data['firstname']) ? $data['firstname'] : '';
        $customer->lastname = !empty($data['lastname']) ? $data['lastname'] : '';
        $customer->company = !empty($data['company']) ? $data['company'] : '';
        $customer->email = !empty($data['email']) ? $data['email'] : '';

        $customer->newsletter = 0;
        $customer->optin = 0;
        $customer->is_guest = 1;
        $customer->active = 1;

        try {
            $customer->update();
        } catch (Exception $e) {
            die($e->getMessage());
            return null;
        }

        return $customer;
    }

    public function createAddress(Customer $customer, array $data, $useShipping = false)
    {
        $data = ($useShipping && !empty($data['Shipping'])) ? $data['Shipping'] : $data['Billing'];

        $address = new Address();
        $address->id_customer = $customer->id;
        $address->alias = ($useShipping) ? 'Shipping' : 'Billing';
        $address->firstname = !empty($data['firstname']) ? $data['firstname'] : '';
        $address->lastname = !empty($data['lastname']) ? $data['lastname'] : '';
        $address->company = !empty($data['company']) ? $data['company'] : '';
        $address->phone = !empty($data['phone']) ? $data['phone'] : '';
        $address->address1 = !empty($data['street']) ? $data['street'] : '';
        $address->address2 = !empty($data['street2']) ? $data['street2'] : '';
        $address->postcode = !empty($data['zip']) ? $data['zip'] : '';
        $address->city = !empty($data['city']) ? $data['city'] : '';
        $address->country = !empty($data['country']) ? $data['country'] : '';

        if ($address->country) {
            $address->id_country = Country::getByIso($address->country);
        }

        try {
            $address->save();
        } catch (Exception $e) {
            die($e->getMessage());
            return null;
        }

        return $address;
    }
}
