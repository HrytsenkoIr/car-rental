<?php

namespace App\Providers;

use App\Repositories\BookingRepository;
use App\Repositories\BookingRepositoryInterface;
use App\Repositories\CarRepository;
use App\Repositories\CarRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            BookingRepositoryInterface::class,
            BookingRepository::class
        );

        $this->app->bind(
            CarRepositoryInterface::class,
            CarRepository::class
        );
    }

    public function boot(): void {}
}
