<?php

/**
 * Created by PhpStorm.
 * User: jesperjohansson
 * Date: 15-09-09
 * Time: 21:25
 */
class BillmateInvoice extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'billmateinvoice';
    }
}