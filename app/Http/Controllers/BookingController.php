<?php

namespace App\Http\Controllers;

use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $service
    ) {}

    public function calendar(Request $request): JsonResponse
    {
        $year  = (int) $request->query('year',  now()->year);
        $month = (int) $request->query('month', now()->month);
        $companyId = (int) $request->query('company_id', 1);
        $showHourly = $request->boolean('show_hourly');

        if ($month < 1 || $month > 12) {
            return response()->json(['error' => 'Invalid month'], 422);
        }

        if ($companyId < 1) {
            return response()->json(['error' => 'Invalid company'], 422);
        }

        $data = $this->service->getCalendarData($year, $month, $showHourly, $companyId);

        return response()->json($data);
    }
}
