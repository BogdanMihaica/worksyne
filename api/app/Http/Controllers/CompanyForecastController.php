<?php

namespace App\Http\Controllers;

use App\Services\CompanyWorkloadForecaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyForecastController extends Controller
{
    public function show(Request $request, CompanyWorkloadForecaster $forecaster): JsonResponse
    {
        $companyId = $request->user()?->companyUser?->company_id;

        if (! $companyId) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        return response()->json($forecaster->forecast($companyId));
    }
}
