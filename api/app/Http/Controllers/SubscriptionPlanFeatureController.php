<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlanFeature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SubscriptionPlanFeatureController extends ApiResourceController
{
    protected string $modelClass = SubscriptionPlanFeature::class;

    public function index(): JsonResponse
    {
        return response()->json(
            QueryBuilder::for(SubscriptionPlanFeature::class)
                ->allowedFilters(
                    AllowedFilter::exact('subscription_plan_id'),
                    AllowedFilter::exact('feature_id'),
                )
                ->allowedSorts('created_at')
                ->paginate()
                ->appends(request()->query())
        );
    }

    protected function storeRules(): array
    {
        return [
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plan,id'],
            'feature_id' => ['required', 'integer', 'exists:feature,id'],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'subscription_plan_id' => ['sometimes', 'required', 'integer', 'exists:subscription_plan,id'],
            'feature_id' => ['sometimes', 'required', 'integer', 'exists:feature,id'],
        ];
    }
}
