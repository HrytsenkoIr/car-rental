<?php

namespace App\Services;

class CarBodyService
{
    public function getBodyTypeName(int $bodyId): string
    {
        return match($bodyId) {
            1, 4, 14 => 'sedan',
            9, 11    => 'suv',
            0, 7     => 'convertible',
            6        => 'coupe',
            8        => 'pickup',
            3        => 'hatchback',
            12, 13   => 'van',
            default  => 'other',
        };
    }
}
