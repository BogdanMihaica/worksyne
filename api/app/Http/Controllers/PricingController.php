<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;

class PricingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            SubscriptionPlan::query()
                ->with(['features' => function ($query) {
                    $query->orderBy('name');
                }])
                ->orderBy('price')
                ->get()
        );
    }
}
