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

class UserController extends ApiResourceController
{
    protected string $modelClass = User::class;

    public function index(): JsonResponse
    {
        return response()->json(
            QueryBuilder::for(User::query()->with(['companyUser.company']))
                ->allowedFilters(
                    'name',
                    'email',
                    AllowedFilter::exact('admin', 'is_admin'),
                    AllowedFilter::exact('company_user_id'),
                )
                ->allowedSorts('name', 'email', 'company_user_id', 'created_at')
                ->paginate()
                ->appends(request()->query())
        );
    }

    public function withoutCompany(): JsonResponse
    {
        return response()->json(
            User::query()
                ->with(['companyUser.company'])
                ->whereHas('companyUser', function ($query) {
                    $query->whereNull('company_id');
                })
                ->orderBy('name')
                ->get()
        );
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->findModel($id)->load(['companyUser.company']));
    }

    public function store(Request $request): JsonResponse
    {
        $attributes = $this->validatedAttributes($request, $this->storeRules());
        $companyUserAttributes = $attributes['company_user'] ?? null;
        unset($attributes['company_user']);

        $user = User::query()->create($attributes);

        $this->saveCompanyUser($user, $companyUserAttributes);

        return response()->json($user->load(['companyUser.company']), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->findModel($id);
        $attributes = $this->validatedAttributes($request, $this->updateRules($user));
        $companyUserAttributes = $attributes['company_user'] ?? null;
        unset($attributes['company_user']);

        $user->update($attributes);
        $this->saveCompanyUser($user, $companyUserAttributes);

        return response()->json($user->load(['companyUser.company']));
    }

    protected function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:user,email'],
            'email_verified_at' => ['nullable', 'date'],
            'password' => ['required', 'string', 'min:8'],
            'company_user_id' => ['nullable', 'integer', 'exists:company_user,id'],
            'is_admin' => ['sometimes', 'boolean'],
            'is_email_verified' => ['sometimes', 'boolean'],
            'is_blocked' => ['sometimes', 'boolean'],
            'company_user' => ['sometimes', 'array'],
            'company_user.company_id' => ['nullable', 'integer', 'exists:company,id'],
            'company_user.external_id' => ['nullable', 'string', 'max:255'],
            'company_user.role' => ['nullable', Rule::in(['company_admin', 'team_lead', 'worker'])],
            'company_user.status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('user', 'email')->ignore($model->getKey())],
            'email_verified_at' => ['sometimes', 'nullable', 'date'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'company_user_id' => ['sometimes', 'nullable', 'integer', 'exists:company_user,id'],
            'is_admin' => ['sometimes', 'boolean'],
            'is_email_verified' => ['sometimes', 'boolean'],
            'is_blocked' => ['sometimes', 'boolean'],
            'company_user' => ['sometimes', 'array'],
            'company_user.company_id' => ['nullable', 'integer', 'exists:company,id'],
            'company_user.external_id' => ['nullable', 'string', 'max:255'],
            'company_user.role' => ['nullable', Rule::in(['company_admin', 'team_lead', 'worker'])],
            'company_user.status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
        ];
    }

    protected function validatedAttributes(Request $request, array $rules): array
    {
        $attributes = parent::validatedAttributes($request, $rules);

        if (array_key_exists('password', $attributes) && $attributes['password'] === null) {
            unset($attributes['password']);
        }

        return $attributes;
    }

    private function saveCompanyUser(User $user, ?array $attributes): void
    {
        if (! $attributes || empty($attributes['role'])) {
            return;
        }

        $companyUser = $user->companyUser ?: CompanyUser::query()->create([
            'company_id' => $attributes['company_id'] ?? null,
            'external_id' => $attributes['external_id'] ?? null,
            'role' => $attributes['role'],
            'status' => $attributes['status'] ?? 'pending',
        ]);

        if (! $user->company_user_id) {
            $user->forceFill(['company_user_id' => $companyUser->id])->save();
        }

        $companyUser->update([
            'company_id' => $attributes['company_id'] ?? null,
            'external_id' => $attributes['external_id'] ?? null,
            'role' => $attributes['role'],
            'status' => $attributes['status'] ?? 'pending',
        ]);
    }
}
