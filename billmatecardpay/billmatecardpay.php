<?php

/**
 * Created by PhpStorm.
 * User: jesperjohansson
 * Date: 15-09-09
 * Time: 21:27
 */
class BillmateCardpay extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'billmatecardpay';

    }
}