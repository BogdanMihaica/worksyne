<?php

namespace App\Http\Controllers;

use App\Models\CompanyUserSeniority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CompanyUserSeniorityController extends ApiResourceController
{
    protected string $modelClass = CompanyUserSeniority::class;

    public function index(): JsonResponse
    {
        return response()->json(
            QueryBuilder::for(CompanyUserSeniority::class)
                ->allowedFilters(
                    AllowedFilter::exact('company_id'),
                    AllowedFilter::exact('user_id'),
                    AllowedFilter::exact('workstream_id'),
                    AllowedFilter::exact('seniority'),
                )
                ->allowedSorts('seniority', 'created_at')
                ->paginate()
                ->appends(request()->query())
        );
    }

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

    public function sync(Request $request): JsonResponse
    {
        $attributes = $request->validate([
            'company_id' => ['required', 'integer', 'exists:company,id'],
            'user_id' => ['required', 'integer', 'exists:user,id'],
            'items' => ['array'],
            'items.*.workstream_id' => ['required', 'integer', 'exists:workstream,id'],
            'items.*.seniority' => ['required', Rule::in(['intern', 'junior', 'mid', 'senior'])],
        ]);

        CompanyUserSeniority::query()
            ->where('company_id', $attributes['company_id'])
            ->where('user_id', $attributes['user_id'])
            ->delete();

        foreach ($attributes['items'] ?? [] as $item) {
            CompanyUserSeniority::query()->create([
                'company_id' => $attributes['company_id'],
                'user_id' => $attributes['user_id'],
                'workstream_id' => $item['workstream_id'],
                'seniority' => $item['seniority'],
            ]);
        }

        return response()->json(
            CompanyUserSeniority::query()
                ->where('company_id', $attributes['company_id'])
                ->where('user_id', $attributes['user_id'])
                ->get()
        );
    }
}
