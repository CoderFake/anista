<?php

namespace App\Repositories\Interfaces;

/**
 * Interface WardServiceInterface
 * @package App\Services\Interfaces
 */
interface WardRepositoryInterface
{
    public function findWardByDistrictId(int $district_id);
}