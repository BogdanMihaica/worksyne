<?php

namespace App\Http\Controllers;

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
            QueryBuilder::for(User::class)
                ->allowedFilters(
                    'name',
                    'email',
                    AllowedFilter::exact('admin', 'is_admin'),
                )
                ->allowedSorts('name', 'email', 'created_at')
                ->paginate()
                ->appends(request()->query())
        );
    }

    protected function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:user,email'],
            'email_verified_at' => ['nullable', 'date'],
            'password' => ['required', 'string', 'min:8'],
            'is_admin' => ['sometimes', 'boolean'],
            'is_email_verified' => ['sometimes', 'boolean'],
            'is_blocked' => ['sometimes', 'boolean'],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('user', 'email')->ignore($model->getKey())],
            'email_verified_at' => ['sometimes', 'nullable', 'date'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'is_admin' => ['sometimes', 'boolean'],
            'is_email_verified' => ['sometimes', 'boolean'],
            'is_blocked' => ['sometimes', 'boolean'],
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
}
