<?php

namespace App\Repositories;

interface BookingRepositoryInterface
{
    public function getBookingsForPeriod(
        int    $companyId,
        string $startDate,
        string $endDate
    ): array;
}
