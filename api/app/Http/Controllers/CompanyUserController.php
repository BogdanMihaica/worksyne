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
                    AllowedFilter::exact('external_id'),
                    AllowedFilter::exact('role'),
                    AllowedFilter::exact('status'),
                )
                ->allowedSorts('external_id', 'role', 'status', 'created_at')
                ->paginate()
                ->appends(request()->query())
        );
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->findModel($id)->load(['company', 'user']));
    }

    protected function storeRules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:company,id'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:user,id'],
            'role' => ['sometimes', 'required', Rule::in(['company_admin', 'team_lead', 'worker'])],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'approved', 'rejected'])],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'company_id' => ['sometimes', 'nullable', 'integer', 'exists:company,id'],
            'external_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:user,id'],
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
        $shouldUpdateUser = array_key_exists('user_id', $attributes);
        $userId = $attributes['user_id'] ?? null;
        unset($attributes['user_id']);

        $companyUser->update($attributes);

        if ($shouldUpdateUser) {
            User::query()->where('company_user_id', $companyUser->id)->update(['company_user_id' => null]);

            if ($userId !== null) {
                User::query()->whereKey($userId)->update(['company_user_id' => $companyUser->id]);
            }
        }

        return response()->json($companyUser->load(['company', 'user']));
    }
}
