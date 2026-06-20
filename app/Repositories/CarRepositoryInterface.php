<?php

namespace App\Repositories;

interface CarRepositoryInterface
{
    public function getCarsByCompany(int $companyId): array;
}
