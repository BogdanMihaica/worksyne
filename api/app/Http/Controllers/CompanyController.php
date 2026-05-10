<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CompanyController extends ApiResourceController
{
    protected string $modelClass = Company::class;

    protected function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:company,name'],
            'owner_id' => ['required', 'integer', 'exists:user,id'],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('company', 'name')->ignore($model->getKey())],
            'owner_id' => ['sometimes', 'required', 'integer', 'exists:user,id'],
        ];
    }
}
