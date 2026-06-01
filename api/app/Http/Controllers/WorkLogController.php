<?php

namespace App\Http\Controllers;

use App\Models\UserWorkstream;
use App\Models\Workstream;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class WorkLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()?->companyUser?->company_id;

        if (! $companyId) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $query = UserWorkstream::query()
            ->with('workstream')
            ->where('user_id', $request->user()->id)
            ->whereHas('workstream', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            });

        $workLogs = QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::exact('workstream_id'),
                'unique_code',
                'reference_code',
                'note',
                AllowedFilter::exact('units'),
            )
            ->allowedSorts('created_at', 'units', 'reference_code', 'workstream_id')
            ->paginate()
            ->appends(request()->query());

        return response()->json($workLogs);
    }

    public function workstreams(Request $request): JsonResponse
    {
        $companyId = $request->user()?->companyUser?->company_id;

        if (! $companyId) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $workstreams = Workstream::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return response()->json($workstreams);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $request->user()?->companyUser?->company_id;

        if (! $companyId) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $attributes = $request->validate([
            'workstream_id' => [
                'required',
                'integer',
                Rule::exists('workstream', 'id')->where('company_id', $companyId),
            ],
            'unique_code' => ['nullable', 'string', 'max:255', 'unique:user_workstream,unique_code'],
            'units' => ['required', 'integer', 'min:1', 'max:65535'],
            'reference_code' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $attributes['user_id'] = $request->user()->id;

        $workLog = UserWorkstream::query()->create($attributes);

        return response()->json($workLog->load('workstream'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()?->companyUser?->company_id;

        if (! $companyId) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $workLog = UserWorkstream::query()
            ->with('workstream')
            ->whereKey($id)
            ->where('user_id', $request->user()->id)
            ->whereHas('workstream', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->firstOrFail();

        $attributes = $request->validate([
            'workstream_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('workstream', 'id')->where('company_id', $companyId),
            ],
            'unique_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('user_workstream', 'unique_code')->ignore($workLog->getKey()),
            ],
            'units' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'reference_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'note' => ['sometimes', 'nullable', 'string'],
        ]);

        $workLog->update($attributes);

        return response()->json($workLog->fresh()->load('workstream'));
    }
}
