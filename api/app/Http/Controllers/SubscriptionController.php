<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SubscriptionController extends ApiResourceController
{
    protected string $modelClass = Subscription::class;

    public function index(): JsonResponse
    {
        return response()->json(
            QueryBuilder::for(Subscription::query()->with(['company', 'subscriptionPlan']))
                ->allowedFilters(
                    AllowedFilter::exact('company_id'),
                    AllowedFilter::exact('subscription_plan_id'),
                    AllowedFilter::exact('status'),
                    AllowedFilter::exact('starts_at'),
                    AllowedFilter::exact('ends_at'),
                )
                ->allowedSorts('starts_at', 'ends_at', 'status', 'created_at')
                ->paginate()
                ->appends(request()->query())
        );
    }

    protected function storeRules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:company,id'],
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plan,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'canceled', 'expired'])],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'company_id' => ['sometimes', 'required', 'integer', 'exists:company,id'],
            'subscription_plan_id' => ['sometimes', 'required', 'integer', 'exists:subscription_plan,id'],
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'canceled', 'expired'])],
        ];
    }
}
