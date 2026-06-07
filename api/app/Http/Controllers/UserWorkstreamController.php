<?php

namespace App\Http\Controllers;

use App\Models\UserWorkstream;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserWorkstreamController extends ApiResourceController
{
    protected string $modelClass = UserWorkstream::class;

    public function index(): JsonResponse
    {
        return response()->json(
            QueryBuilder::for(UserWorkstream::class)
                ->allowedFilters(
                    AllowedFilter::exact('user_id'),
                    AllowedFilter::exact('workstream_id'),
                    'unique_code',
                    'reference_code',
                    AllowedFilter::exact('units'),
                    AllowedFilter::exact('logged_on'),
                )
                ->allowedSorts('unique_code', 'reference_code', 'units', 'logged_on', 'created_at')
                ->paginate()
                ->appends(request()->query())
        );
    }

    protected function storeRules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:user,id'],
            'workstream_id' => ['required', 'integer', 'exists:workstream,id'],
            'unique_code' => ['nullable', 'string', 'max:255', 'unique:user_workstream,unique_code'],
            'units' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'logged_on' => ['required', 'date', 'before_or_equal:today'],
            'reference_code' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'user_id' => ['sometimes', 'required', 'integer', 'exists:user,id'],
            'workstream_id' => ['sometimes', 'required', 'integer', 'exists:workstream,id'],
            'unique_code' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('user_workstream', 'unique_code')->ignore($model->getKey())],
            'units' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'logged_on' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'reference_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'note' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
