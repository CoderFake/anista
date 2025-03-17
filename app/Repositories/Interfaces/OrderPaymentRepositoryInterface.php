<?php

namespace App\Repositories\Interfaces;

/**
 * Interface OrderPaymentRepositoryInterface
 * @package App\Repositories\Interfaces
 */
interface OrderPaymentRepositoryInterface
{
    public function create(array $payload);
    public function findByOrderId(int $orderId);
}