<?php

namespace App\Repositories;

use App\Models\OrderPayment;
use App\Repositories\Interfaces\OrderPaymentRepositoryInterface;
use App\Repositories\BaseRepository;

/**
 * Class OrderPaymentRepository
 * @package App\Repositories
 */
class OrderPaymentRepository extends BaseRepository implements OrderPaymentRepositoryInterface
{
    protected $model;

    public function __construct(
        OrderPayment $model
    ){
        $this->model = $model;
    }

    public function findByOrderId(int $orderId)
    {
        return $this->model->where('order_id', $orderId)->first();
    }
}