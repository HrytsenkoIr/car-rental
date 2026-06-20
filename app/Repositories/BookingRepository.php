<?php

namespace App\Repositories;

use App\Models\Booking;

class BookingRepository implements BookingRepositoryInterface
{
    public function getBookingsForPeriod(
        int    $companyId,
        string $startDate,
        string $endDate
    ): array {
        return Booking::query()
            ->from('rc_bookings as b')
            ->join('rc_cars as c', 'b.car_id', '=', 'c.car_id')
            ->where('b.status', 1)
            ->where('c.company_id', $companyId)
            ->where('c.status', 1)
            ->where(function ($q) {
                $q->where('c.is_deleted', '!=', 1)
                    ->orWhereNull('c.is_deleted');
            })
            ->where('b.end_date', '>', $startDate)
            ->where('b.start_date', '<', $endDate)
            ->select(
                'b.booking_id',
                'b.car_id',
                'b.start_date',
                'b.end_date',
                'b.status',
                'b.source'
            )
            ->orderBy('b.car_id')
            ->orderBy('b.start_date')
            ->get()
            ->all();
    }
}
