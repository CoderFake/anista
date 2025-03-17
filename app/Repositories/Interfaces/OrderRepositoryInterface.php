<?php

namespace App\Repositories\Interfaces;

/**
 * Interface AttributeServiceInterface
 * @package App\Services\Interfaces
 */
interface OrderRepositoryInterface
{
    public function findById(int $id);
    public function create(array $payload);
    public function update(int $id, array $payload);
    public function findByCondition(array $condition = [], $flag = false, $relation = [], $orderBy = ['id', 'desc']);
    public function getOrderById($id);
    public function getOrderByTime($month, $year);
    public function getTotalOrders();
    public function getCancleOrders();
    public function revenueOrders();
    public function revenueByYear($year);
    public function revenue7Day();
    public function revenueCurrentMonth($currentMonth, $currentYear);
    public function orderByCustomer($customer_id, $condition);
}