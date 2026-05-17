<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CompanyController extends ApiResourceController
{
    protected string $modelClass = Company::class;

    public function index(): JsonResponse
    {
        return response()->json(
            QueryBuilder::for(Company::query()->with(['owner', 'subscriptionPlan']))
                ->allowedFilters(
                    'name',
                    AllowedFilter::callback('owner_email', function ($query, $value) {
                        $query->whereHas('owner', function ($query) use ($value) {
                            $query->where('email', 'like', '%'.$value.'%');
                        });
                    }),
                    AllowedFilter::exact('owner_id'),
                    AllowedFilter::exact('subscription_plan_id'),
                )
                ->allowedSorts('name', 'created_at')
                ->paginate()
                ->appends(request()->query())
        );
    }

    protected function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:company,name'],
            'subscription_plan_id' => ['nullable', 'integer', 'exists:subscription_plan,id'],
            'owner_id' => ['required', 'integer', 'exists:user,id'],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('company', 'name')->ignore($model->getKey())],
            'subscription_plan_id' => ['sometimes', 'nullable', 'integer', 'exists:subscription_plan,id'],
            'owner_id' => ['sometimes', 'required', 'integer', 'exists:user,id'],
        ];
    }
}
