<?php

namespace App\Http\Controllers;

use App\Models\TimeoffRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TimeoffRequestController extends ApiResourceController
{
    protected string $modelClass = TimeoffRequest::class;

    public function index(): JsonResponse
    {
        return response()->json(
            QueryBuilder::for(TimeoffRequest::class)
                ->allowedFilters(
                    AllowedFilter::exact('user_id'),
                    AllowedFilter::exact('start_date'),
                    AllowedFilter::exact('end_date'),
                    AllowedFilter::exact('status'),
                )
                ->allowedSorts('start_date', 'end_date', 'status', 'created_at')
                ->paginate()
                ->appends(request()->query())
        );
    }

    protected function storeRules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:user,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'approved', 'rejected'])],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'user_id' => ['sometimes', 'required', 'integer', 'exists:user,id'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'approved', 'rejected'])],
        ];
    }
}
