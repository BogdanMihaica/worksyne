<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SubscriptionPlanController extends ApiResourceController
{
    protected string $modelClass = SubscriptionPlan::class;

    public function index(): JsonResponse
    {
        return response()->json(
            QueryBuilder::for(SubscriptionPlan::class)
                ->allowedFilters(
                    'name',
                    AllowedFilter::exact('price'),
                )
                ->allowedSorts('name', 'price', 'created_at')
                ->paginate()
                ->appends(request()->query())
        );
    }

    protected function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:subscription_plan,name'],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('subscription_plan', 'name')->ignore($model->getKey())],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }
}
