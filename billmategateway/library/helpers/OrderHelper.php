<?php

class OrderHelper
{
    public function convertOrderStatus($method, $status)
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

        $orderId = Order::getOrderByCartId($cart->id);

        return ($orderId) ? new Order($orderId) : null;
    }

    public function getOrderByReference($reference)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>')) {
            return Order::getByReference($reference);
        }

        $orderId = Order::getByReference($reference);

        return ($orderId) ? new Order($orderId) : null;
    }

    public function updateOrderStatus(OrderCore $order, $orderStatus)
    {
        $this->createOrderHistory($order, $orderStatus);

        return true;
    }

    public function createOrderHistory(OrderCore $order, $status)
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
