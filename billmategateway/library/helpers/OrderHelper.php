<?php

class OrderHelper
{
    public function convertOrderStatus(string $method, string $status)
    {
        if ($status == 'Cancelled') {
            return Configuration::get('PS_OS_CANCELED');
        } elseif ($status == 'Pending') {
            return Configuration::get('BILLMATE_PAYMENT_PENDING');
        } elseif ($method == 'checkout') {
            return Configuration::get('BILLMATE_CHECKOUT_ORDER_STATUS');
        }

        return Configuration::get('B' . strtoupper($method) . '_ORDER_STATUS');
    }

    public function getOrderByCart(Cart $cart)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>')) {
            return Order::getByCartId($cart->id);
        }

        return new Order(
            Order::getOrderByCartId($cart->id)
        );
    }

    public function getOrderByReference(string $reference)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>')) {
            return Order::getByReference($reference);
        }

        return new Order(
            Order::getByReference($reference)
        );
    }

    public function updateOrderStatus(OrderCore $order, string $orderStatus)
    {
        $this->createOrderHistory($order, $orderStatus);

        return true;
    }

    public function createOrderHistory(OrderCore $order, string $status)
    {
        $orderHistory = new OrderHistory();

        try {
            $orderHistory->id_order = $order->id;
            $orderHistory->changeIdOrderState($status, $order->id, true);
            $orderHistory->add();
        } catch (Exception $e) {
            $this->logEvent('Failed to add order history in helper: '. $e->getMessage());

            return false;
        }

        return $orderHistory;
    }

    public function shouldUpdateOrder(OrderCore $order)
    {
        return ($order->current_state == Configuration::get('BILLMATE_PAYMENT_PENDING') && !$this->client->isPending()) ? true : false;
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
