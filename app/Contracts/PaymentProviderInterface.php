<?php
namespace App\Contracts;

interface PaymentProviderInterface
{
    public function pay($order_id, $email, $amount);
}
