<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\UserWorkstream;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyOverviewController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $companyId = $request->user()?->companyUser?->company_id;

        if (! $companyId) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $company = Company::query()
            ->with(['owner', 'subscriptionPlan', 'subscriptions.subscriptionPlan'])
            ->withCount(['users', 'workstreams'])
            ->findOrFail($companyId);

        $activeSubscription = $company->subscriptions
            ->where('status', 'active')
            ->sortByDesc('starts_at')
            ->first();

        $topWorkers = UserWorkstream::query()
            ->select([
                'user.id',
                'user.name',
                'user.email',
                DB::raw('COUNT(user_workstream.id) as workstream_count'),
                DB::raw('SUM(user_workstream.units) as units'),
            ])
            ->join('user', 'user.id', '=', 'user_workstream.user_id')
            ->join('workstream', 'workstream.id', '=', 'user_workstream.workstream_id')
            ->where('workstream.company_id', $companyId)
            ->whereBetween('user_workstream.created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->groupBy('user.id', 'user.name', 'user.email')
            ->orderByDesc('workstream_count')
            ->orderByDesc('units')
            ->limit(5)
            ->get();

        return response()->json([
            'company' => $company,
            'active_subscription' => $activeSubscription,
            'top_workers' => $topWorkers,
        ]);
    }
}
