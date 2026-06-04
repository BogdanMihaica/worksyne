<?php

namespace App\Http\Controllers;

use App\Models\CapacityModel;
use App\Models\Workstream;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function store(Request $request): JsonResponse
    {
        $attributes = $this->validatedAttributes($request, $this->storeRules());

        $workstream = DB::transaction(function () use ($attributes) {
            $capacityModels = $attributes['capacity_models'];
            unset($attributes['capacity_models']);

            $workstream = Workstream::query()->create($attributes);

            foreach ($capacityModels as $capacityModel) {
                CapacityModel::query()->create([
                    'company_id' => $workstream->company_id,
                    'workstream_id' => $workstream->id,
                    'seniority' => $capacityModel['seniority'],
                    'units_per_hour' => $capacityModel['units_per_hour'],
                ]);
            }

            return $workstream;
        });

        return response()->json($workstream, 201);
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
            'capacity_models' => ['required', 'array', 'size:'.count(CapacityModel::SENIORITIES)],
            'capacity_models.*.seniority' => ['required', 'distinct', Rule::in(CapacityModel::SENIORITIES)],
            'capacity_models.*.units_per_hour' => ['required', 'numeric', 'min:0', 'max:999999.99'],
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
