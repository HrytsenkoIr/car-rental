<?php

namespace App\Services;

use App\Repositories\BookingRepositoryInterface;
use App\Repositories\CarRepositoryInterface;
use Carbon\Carbon;

class BookingService
{
    private const TIMEZONE        = 'Europe/Kiev';
    private const DAY_START       = 9;
    private const DAY_END         = 21;
    private const FREE_HOURS      = 9;
    private const SOURCE_SERVICE  = 'car-service';
    private const SOURCE_HOURLY   = 'hourly';

    public function __construct(
        private readonly BookingRepositoryInterface $repository,
        private readonly CarRepositoryInterface     $carRepository,
        private readonly CarBodyService             $carBodyService,
    ) {}

    public function getCalendarData(int $year, int $month, bool $showHourly = false, int $companyId = 1): array
    {
        $tz = self::TIMEZONE;

        $periodStart = Carbon::createFromDate($year, $month, 1, $tz)
            ->startOfDay()->utc()->toDateTimeString();

        $periodEnd = Carbon::createFromDate($year, $month, 1, $tz)
            ->endOfMonth()->endOfDay()->utc()->toDateTimeString();

        $daysInMonth = Carbon::createFromDate($year, $month, 1, $tz)->daysInMonth;

        $cars     = $this->carRepository->getCarsByCompany($companyId);
        $bookings = $this->repository->getBookingsForPeriod(
            $companyId, $periodStart, $periodEnd
        );

        $bookingsByCarId = [];
        foreach ($bookings as $b) {
            $bookingsByCarId[$b->car_id][] = $b;
        }

        $result = [];
        foreach ($cars as $car) {
            $carBookings = $bookingsByCarId[$car->car_id] ?? [];
            $daySummary  = $this->calcDaySummary(
                $carBookings, $year, $month, $daysInMonth, $tz
            );

            $result[] = [
                'id'        => $car->car_id,
                'name'      => trim($car->full_name),
                'year'      => $car->attribute_year,
                'number'    => $car->registration_number,
                'body_type' => $this->carBodyService->getBodyTypeName((int) $car->car_body_id),
                'color'     => $car->attribute_color ?: 'Unknown',
                'type'      => $car->type ?: 'Unknown',
                'free'      => $daySummary['free'],
                'busy'      => $daySummary['busy'],
                'service'   => $daySummary['service'],
                'all'       => $daysInMonth,
                'rented_label' => 'Rented for ' . $daySummary['busy'] . ' ' . ($daySummary['busy'] === 1 ? 'day' : 'days'),
                'days'      => $daySummary['days'],
                'hourly_offers' => $showHourly
                    ? $this->getHourlyOffers($carBookings, $year, $month, $daysInMonth, $tz)
                    : [],
            ];
        }

        return [
            'cars'          => $result,
            'month'         => $month,
            'year'          => $year,
            'days_in_month' => $daysInMonth,
        ];
    }

    private function calcDaySummary(
        array  $carBookings,
        int    $year,
        int    $month,
        int    $daysInMonth,
        string $tz
    ): array {
        $free = $busy = $service = 0;
        $days = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dayStart = Carbon::create($year, $month, $day, self::DAY_START, 0, 0, $tz);
            $dayEnd   = Carbon::create($year, $month, $day, self::DAY_END,   0, 0, $tz);

            $overlapping = $this->getOverlappingBookings(
                $carBookings, $dayStart, $dayEnd, $tz
            );

            if (empty($overlapping)) {
                $free++;
                $days[$day] = ['day' => $day, 'status' => 'free'];
                continue;
            }

            $hasServiceOnly = $this->allSourcesAre($overlapping, self::SOURCE_SERVICE);
            $maxFreeGap     = $this->maxFreeGapHours($overlapping, $dayStart, $dayEnd);

            if ($hasServiceOnly) {
                if ($maxFreeGap >= self::FREE_HOURS) {
                    $free++;
                    $days[$day] = ['day' => $day, 'status' => 'free'];
                } else {
                    $service++;
                    $days[$day] = ['day' => $day, 'status' => 'service'];
                }
            } else {
                if ($maxFreeGap >= self::FREE_HOURS) {
                    $free++;
                    $days[$day] = ['day' => $day, 'status' => 'free'];
                } else {
                    $busy++;
                    $days[$day] = ['day' => $day, 'status' => 'rented'];
                }
            }
        }

        return ['free' => $free, 'busy' => $busy, 'service' => $service, 'days' => array_values($days)];
    }

    private function getHourlyOffers(
        array $bookings,
        int $year,
        int $month,
        int $daysInMonth,
        string $tz
    ): array {
        $offers = [];

        foreach ($bookings as $booking) {
            if ($booking->source !== self::SOURCE_HOURLY) {
                continue;
            }

            $start = Carbon::parse($booking->start_date)->setTimezone($tz);
            $end = Carbon::parse($booking->end_date)->setTimezone($tz);

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dayStart = Carbon::create($year, $month, $day, 0, 0, 0, $tz);
                $dayEnd = $dayStart->copy()->endOfDay();

                if (!$start->lt($dayEnd) || !$end->gt($dayStart)) {
                    continue;
                }

                $visibleStart = $start->greaterThan($dayStart) ? $start : $dayStart;
                $visibleEnd = $end->lessThan($dayEnd) ? $end : $dayEnd;

                $offers[] = [
                    'booking_id' => $booking->booking_id,
                    'day' => $day,
                    'start' => $visibleStart->format('H:i'),
                    'end' => $visibleEnd->format('H:i'),
                    'label' => $visibleStart->format('H:i') . '-' . $visibleEnd->format('H:i'),
                ];
            }
        }

        return $offers;
    }

    private function getOverlappingBookings(
        array  $bookings,
        Carbon $windowStart,
        Carbon $windowEnd,
        string $tz,
        string $excludeSource = ''
    ): array {
        $result = [];
        foreach ($bookings as $b) {
            if ($excludeSource && $b->source === $excludeSource) {
                continue;
            }
            $bStart = Carbon::parse($b->start_date)->setTimezone($tz);
            $bEnd   = Carbon::parse($b->end_date)->setTimezone($tz);

            if ($bStart->lt($windowEnd) && $bEnd->gt($windowStart)) {
                $result[] = [
                    'start'  => $bStart,
                    'end'    => $bEnd,
                    'source' => $b->source,
                ];
            }
        }
        return $result;
    }


    private function allSourcesAre(array $bookings, string $source): bool
    {
        foreach ($bookings as $b) {
            if ($b['source'] !== $source) {
                return false;
            }
        }
        return true;
    }


    private function maxFreeGapHours(
        array  $bookings,
        Carbon $windowStart,
        Carbon $windowEnd
    ): float {
        $intervals = [];
        foreach ($bookings as $b) {
            $s = max($b['start']->timestamp, $windowStart->timestamp);
            $e = min($b['end']->timestamp,   $windowEnd->timestamp);
            if ($s < $e) {
                $intervals[] = [$s, $e];
            }
        }

        if (empty($intervals)) {
            return ($windowEnd->timestamp - $windowStart->timestamp) / 3600;
        }

        usort($intervals, fn($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($intervals as [$s, $e]) {
            if (empty($merged)) {
                $merged[] = [$s, $e];
            } else {
                $last = &$merged[count($merged) - 1];
                if ($s <= $last[1]) {
                    $last[1] = max($last[1], $e);
                } else {
                    $merged[] = [$s, $e];
                }
            }
        }

        $gaps = [];
        $gaps[] = $merged[0][0] - $windowStart->timestamp;
        for ($i = 0; $i < count($merged) - 1; $i++) {
            $gaps[] = $merged[$i + 1][0] - $merged[$i][1];
        }
        $gaps[] = $windowEnd->timestamp - end($merged)[1];

        return max($gaps) / 3600;
    }
}

