<?php

namespace App\Http\Controllers;

use App\Models\Workstream;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class WorkstreamController extends ApiResourceController
{
    protected string $modelClass = Workstream::class;

    public function index(): JsonResponse
    {
        return response()->json(
            QueryBuilder::for(Workstream::class)
                ->allowedFilters(
                    AllowedFilter::exact('company_id'),
                    'name',
                )
                ->allowedSorts('name', 'created_at')
                ->paginate()
                ->appends(request()->query())
        );
    }

    protected function storeRules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:company,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('workstream', 'name')->where('company_id', request('company_id')),
            ],
        ];
    }

    protected function updateRules(Model $model): array
    {
        $companyId = request('company_id', $model->company_id);

        return [
            'company_id' => ['sometimes', 'required', 'integer', 'exists:company,id'],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('workstream', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($model->getKey()),
            ],
        ];
    }
}
