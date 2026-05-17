<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\QueryBuilder;

class FeatureController extends ApiResourceController
{
    protected string $modelClass = Feature::class;

    public function index(): JsonResponse
    {
        return response()->json(
            QueryBuilder::for(Feature::class)
                ->allowedFilters('name', 'key', 'description')
                ->allowedSorts('name', 'key', 'created_at')
                ->paginate()
                ->appends(request()->query())
        );
    }

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
