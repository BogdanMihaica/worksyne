<?php

namespace App\Http\Controllers;

use App\Models\CompanyUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CompanyUserController extends ApiResourceController
{
    protected string $modelClass = CompanyUser::class;

    public function index(): JsonResponse
    {
        return response()->json(
            QueryBuilder::for(CompanyUser::query()->with(['company', 'user']))
                ->allowedFilters(
                    AllowedFilter::callback('name', function ($query, $value) {
                        $query->whereHas('user', function ($query) use ($value) {
                            $query->where('name', 'like', '%'.$value.'%');
                        });
                    }),
                    AllowedFilter::callback('email', function ($query, $value) {
                        $query->whereHas('user', function ($query) use ($value) {
                            $query->where('email', 'like', '%'.$value.'%');
                        });
                    }),
                    AllowedFilter::callback('company', function ($query, $value) {
                        $query->whereHas('company', function ($query) use ($value) {
                            $query->where('name', 'like', '%'.$value.'%');
                        });
                    }),
                    AllowedFilter::exact('company_id'),
                    AllowedFilter::exact('user_id'),
                    AllowedFilter::exact('role'),
                    AllowedFilter::exact('status'),
                )
                ->allowedSorts('role', 'status', 'created_at')
                ->paginate()
                ->appends(request()->query())
        );
    }

    protected function storeRules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:company,id'],
            'user_id' => ['required', 'integer', 'exists:user,id'],
            'role' => ['sometimes', 'required', Rule::in(['company_admin', 'team_lead', 'worker'])],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'approved', 'rejected'])],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'company_id' => ['sometimes', 'required', 'integer', 'exists:company,id'],
            'user_id' => ['sometimes', 'required', 'integer', 'exists:user,id'],
            'role' => ['sometimes', 'required', Rule::in(['company_admin', 'team_lead', 'worker'])],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'approved', 'rejected'])],
        ];
    }
}
