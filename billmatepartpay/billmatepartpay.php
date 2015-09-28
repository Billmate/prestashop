<?php

/**
 * Created by PhpStorm.
 * User: jesperjohansson
 * Date: 15-09-09
 * Time: 21:21
 */
class BillmatePartpay extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'billmatepartpay';
    }
}