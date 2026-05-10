<?php

namespace App\Http\Controllers;

use App\Models\CompanyUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CompanyUserController extends ApiResourceController
{
    protected string $modelClass = CompanyUser::class;

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
