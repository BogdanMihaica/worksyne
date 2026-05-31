<?php

namespace App\Http\Controllers;

use App\Models\CompanyUserSeniority;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function updateForUser(Request $request, User $user): JsonResponse
    {
        $companyUser = $request->user()?->companyUser;
        $targetCompanyUser = $user->companyUser;

        if (!$companyUser || !$targetCompanyUser || $targetCompanyUser->company_id !== $companyUser->company_id) {
            return response()->json([
                'message' => 'This user is not available for your company.',
            ], 403);
        }

        $attributes = $request->validate([
            'items' => ['array'],
            'items.*.workstream_id' => [
                'required',
                'integer',
                Rule::exists('workstream', 'id')->where('company_id', $companyUser->company_id),
            ],
            'items.*.seniority' => ['required', Rule::in(['intern', 'junior', 'mid', 'senior'])],
        ]);

        $seniorities = DB::transaction(function () use ($attributes, $companyUser, $user) {
            $deleteQuery = CompanyUserSeniority::query()
                ->where('company_id', $companyUser->company_id)
                ->where('user_id', $user->id);

            $deleteQuery->delete();

            foreach ($attributes['items'] ?? [] as $item) {
                CompanyUserSeniority::query()->create([
                    'company_id' => $companyUser->company_id,
                    'user_id' => $user->id,
                    'workstream_id' => $item['workstream_id'],
                    'seniority' => $item['seniority'],
                ]);
            }

            $senioritiesQuery = CompanyUserSeniority::query()
                ->where('company_id', $companyUser->company_id)
                ->where('user_id', $user->id);

            return $senioritiesQuery->get();
        });

        return response()->json($seniorities);
    }
}
