<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
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

    public function ownerOptions(int $id): JsonResponse
    {
        $company = Company::query()->findOrFail($id);

        $users = User::query()
            ->where(function ($query) use ($company) {
                $query->whereHas('companyUser', function ($query) use ($company) {
                    $query->where('company_id', $company->id);
                });

                if ($company->owner_id) {
                    $query->orWhereKey($company->owner_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    protected function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:company,name'],
            'subscription_plan_id' => ['nullable', 'integer', 'exists:subscription_plan,id'],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('company', 'name')->ignore($model->getKey())],
            'subscription_plan_id' => ['sometimes', 'nullable', 'integer', 'exists:subscription_plan,id'],
            'owner_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:user,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($model) {
                    if (! User::query()
                        ->whereKey($value)
                        ->whereHas('companyUser', function ($query) use ($model) {
                            $query->where('company_id', $model->getKey());
                        })
                        ->exists()) {
                        $fail('The selected owner must be assigned to this company.');
                    }
                },
            ],
        ];
    }
}
