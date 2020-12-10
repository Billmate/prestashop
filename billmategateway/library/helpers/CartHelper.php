<?php

class CartHelper
{
    public function getCart($cartId)
    {
        return new Cart($cartId);
    }
}
