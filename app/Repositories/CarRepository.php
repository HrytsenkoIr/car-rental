<?php

namespace App\Repositories;

use App\Models\Car;
use Illuminate\Support\Facades\DB;

class CarRepository implements CarRepositoryInterface
{
    public function getCarsByCompany(int $companyId): array
    {
        return Car::query()
            ->from('rc_cars as c')
            ->leftJoin('rc_cars_models as m', 'c.car_model_id', '=', 'm.car_model_id')
            ->leftJoin('rc_cars_models_translations as mt', function ($join) {
                $join->on('m.car_model_id', '=', 'mt.car_model_id')
                    ->where('mt.lang', '=', 'en');
            })
            ->leftJoin('rc_cars_translations as ct', function ($join) {
                $join->on('c.car_id', '=', 'ct.car_id')
                    ->where('ct.lang', '=', 'en');
            })
            ->where('c.company_id', $companyId)
            ->where('c.status', 1)
            ->where(function ($q) {
                $q->where('c.is_deleted', '!=', 1)
                    ->orWhereNull('c.is_deleted');
            })
            ->orderBy('c.car_id', 'asc')
            ->select(
                'c.car_id',
                'c.registration_number',
                'c.attribute_year',
                DB::raw("COALESCE(mt.name, m.slug, CONCAT('car-', c.car_id)) as full_name"),
                'c.car_body_id',
                'm.type',
                'ct.attribute_color'
            )
            ->get()
            ->all();
    }
}
