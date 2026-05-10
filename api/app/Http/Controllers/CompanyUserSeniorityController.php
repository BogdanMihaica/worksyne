<?php

namespace App\Http\Controllers;

use App\Models\CompanyUserSeniority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CompanyUserSeniorityController extends ApiResourceController
{
    protected string $modelClass = CompanyUserSeniority::class;

    protected function storeRules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:company,id'],
            'user_id' => ['required', 'integer', 'exists:user,id'],
            'workstream_id' => ['required', 'integer', 'exists:workstream,id'],
            'seniority' => ['sometimes', 'required', Rule::in(['intern', 'junior', 'mid', 'senior'])],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'company_id' => ['sometimes', 'required', 'integer', 'exists:company,id'],
            'user_id' => ['sometimes', 'required', 'integer', 'exists:user,id'],
            'workstream_id' => ['sometimes', 'required', 'integer', 'exists:workstream,id'],
            'seniority' => ['sometimes', 'required', Rule::in(['intern', 'junior', 'mid', 'senior'])],
        ];
    }
}
