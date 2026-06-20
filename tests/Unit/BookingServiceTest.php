<?php

namespace Tests\Unit;

use App\Repositories\BookingRepositoryInterface;
use App\Repositories\CarRepositoryInterface;
use App\Services\BookingService;
use App\Services\CarBodyService;
use PHPUnit\Framework\TestCase;
use stdClass;

class BookingServiceTest extends TestCase
{
    public function test_hourly_bookings_are_counted_as_occupied_time(): void
    {
        $service = $this->makeService(
            cars: [$this->car(1)],
            bookings: [
                $this->booking(1, '2023-01-02 09:00:00', '2023-01-02 13:00:00', 'hourly'),
            ],
        );

        $car = $service->getCalendarData(2023, 1)['cars'][0];

        $this->assertSame(30, $car['free']);
        $this->assertSame(1, $car['busy']);
        $this->assertSame('rented', $car['days'][1]['status']);
    }

    public function test_service_booking_uses_same_nine_hour_free_rule(): void
    {
        $service = $this->makeService(
            cars: [$this->car(1)],
            bookings: [
                $this->booking(1, '2023-01-02 09:00:00', '2023-01-02 14:00:00', 'car-service'),
            ],
        );

        $car = $service->getCalendarData(2023, 1)['cars'][0];

        $this->assertSame(30, $car['free']);
        $this->assertSame(1, $car['service']);
        $this->assertSame('service', $car['days'][1]['status']);
    }

    public function test_day_is_free_when_there_are_nine_consecutive_free_hours(): void
    {
        $service = $this->makeService(
            cars: [$this->car(1)],
            bookings: [
                $this->booking(1, '2023-01-02 09:00:00', '2023-01-02 10:00:00', 'dwm'),
            ],
        );

        $car = $service->getCalendarData(2023, 1)['cars'][0];

        $this->assertSame(31, $car['free']);
        $this->assertSame(0, $car['busy']);
        $this->assertSame('free', $car['days'][1]['status']);
    }

    public function test_hourly_offer_times_are_shifted_to_local_timezone(): void
    {
        $service = $this->makeService(
            cars: [$this->car(1)],
            bookings: [
                $this->booking(1, '2023-01-21 15:30:00', '2023-01-21 18:00:00', 'hourly'),
            ],
        );

        $car = $service->getCalendarData(2023, 1, true)['cars'][0];

        $this->assertSame('17:30', $car['hourly_offers'][0]['start']);
        $this->assertSame('20:00', $car['hourly_offers'][0]['end']);
        $this->assertSame('17:30-20:00', $car['hourly_offers'][0]['label']);
    }

    public function test_hourly_offers_are_returned_only_when_requested(): void
    {
        $service = $this->makeService(
            cars: [$this->car(1)],
            bookings: [
                $this->booking(1, '2023-01-21 15:30:00', '2023-01-21 18:00:00', 'hourly'),
            ],
        );

        $withoutHourly = $service->getCalendarData(2023, 1, false)['cars'][0];
        $withHourly = $service->getCalendarData(2023, 1, true)['cars'][0];

        $this->assertSame([], $withoutHourly['hourly_offers']);
        $this->assertCount(1, $withHourly['hourly_offers']);
        $this->assertSame(21, $withHourly['hourly_offers'][0]['day']);
    }

    private function makeService(array $cars, array $bookings): BookingService
    {
        $bookingRepository = new class($bookings) implements BookingRepositoryInterface {
            public function __construct(
                private readonly array $bookings,
            ) {}

            public function getBookingsForPeriod(int $companyId, string $startDate, string $endDate): array
            {
                return $this->bookings;
            }
        };

        $carRepository = new class($cars) implements CarRepositoryInterface {
            public function __construct(
                private readonly array $cars,
            ) {}

            public function getCarsByCompany(int $companyId): array
            {
                return $this->cars;
            }
        };

        return new BookingService($bookingRepository, $carRepository, new CarBodyService());
    }

    private function car(int $id): stdClass
    {
        return (object) [
            'car_id' => $id,
            'full_name' => 'Test Car',
            'attribute_year' => '2023',
            'registration_number' => 'TEST',
            'car_body_id' => 1,
            'attribute_color' => 'Black',
            'type' => 'luxury',
        ];
    }

    private function booking(int $carId, string $start, string $end, string $source): stdClass
    {
        return (object) [
            'booking_id' => random_int(1, 999999),
            'car_id' => $carId,
            'start_date' => $start,
            'end_date' => $end,
            'status' => 1,
            'source' => $source,
        ];
    }
}
