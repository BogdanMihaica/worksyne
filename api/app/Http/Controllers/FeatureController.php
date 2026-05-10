<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class FeatureController extends ApiResourceController
{
    protected string $modelClass = Feature::class;

    protected function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:feature,name'],
            'key' => ['required', 'string', 'max:255', 'unique:feature,key'],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('feature', 'name')->ignore($model->getKey())],
            'key' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('feature', 'key')->ignore($model->getKey())],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
