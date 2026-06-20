<?php

namespace Tests\Feature;

use App\Services\BookingService;
use Mockery\MockInterface;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    public function test_calendar_endpoint_returns_calendar_data(): void
    {
        $this->mock(BookingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getCalendarData')
                ->once()
                ->with(2023, 1, false, 1)
                ->andReturn([
                    'cars' => [
                        [
                            'id' => 3715,
                            'name' => 'Mercedes S Class',
                            'number' => 'R63978',
                            'free' => 19,
                            'busy' => 9,
                            'service' => 3,
                            'all' => 31,
                            'days' => [],
                            'hourly_offers' => [],
                        ],
                    ],
                    'month' => 1,
                    'year' => 2023,
                    'days_in_month' => 31,
                ]);
        });

        $response = $this->getJson('/api/calendar?year=2023&month=1');

        $response
            ->assertOk()
            ->assertJsonPath('year', 2023)
            ->assertJsonPath('month', 1)
            ->assertJsonPath('days_in_month', 31)
            ->assertJsonPath('cars.0.id', 3715)
            ->assertJsonPath('cars.0.number', 'R63978');
    }

    public function test_calendar_endpoint_passes_show_hourly_flag_to_service(): void
    {
        $this->mock(BookingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getCalendarData')
                ->once()
                ->with(2023, 1, true, 1)
                ->andReturn([
                    'cars' => [
                        [
                            'id' => 3715,
                            'hourly_offers' => [
                                [
                                    'booking_id' => 33578,
                                    'day' => 21,
                                    'label' => '17:30-20:00',
                                ],
                            ],
                        ],
                    ],
                    'month' => 1,
                    'year' => 2023,
                    'days_in_month' => 31,
                ]);
        });

        $response = $this->getJson('/api/calendar?year=2023&month=1&show_hourly=1');

        $response
            ->assertOk()
            ->assertJsonPath('cars.0.hourly_offers.0.day', 21)
            ->assertJsonPath('cars.0.hourly_offers.0.label', '17:30-20:00');
    }

    public function test_calendar_endpoint_rejects_invalid_month(): void
    {
        $response = $this->getJson('/api/calendar?year=2023&month=13');

        $response
            ->assertStatus(422)
            ->assertJsonPath('error', 'Invalid month');
    }

    public function test_calendar_endpoint_rejects_invalid_company(): void
    {
        $response = $this->getJson('/api/calendar?year=2023&month=1&company_id=0');

        $response
            ->assertStatus(422)
            ->assertJsonPath('error', 'Invalid company');
    }
}
