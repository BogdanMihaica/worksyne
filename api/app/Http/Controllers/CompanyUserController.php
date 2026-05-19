<?php

namespace App\Http\Controllers;

use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'user_id' => ['sometimes', 'required', 'integer', 'exists:user,id'],
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

    public function store(Request $request): JsonResponse
    {
        $attributes = $this->validatedAttributes($request, $this->storeRules());
        $userId = $attributes['user_id'] ?? null;
        unset($attributes['user_id']);

        $companyUser = CompanyUser::query()->create($attributes);

        if ($userId !== null) {
            User::query()->whereKey($userId)->update(['company_user_id' => $companyUser->id]);
        }

        return response()->json($companyUser->load(['company', 'user']), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $companyUser = $this->findModel($id);
        $attributes = $this->validatedAttributes($request, $this->updateRules($companyUser));
        $userId = $attributes['user_id'] ?? null;
        unset($attributes['user_id']);

        $companyUser->update($attributes);

        if ($userId !== null) {
            User::query()->where('company_user_id', $companyUser->id)->update(['company_user_id' => null]);
            User::query()->whereKey($userId)->update(['company_user_id' => $companyUser->id]);
        }

        return response()->json($companyUser->load(['company', 'user']));
    }
}
