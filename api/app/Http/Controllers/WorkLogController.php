<?php

namespace App\Http\Controllers;

use App\Models\UserWorkstream;
use App\Models\Workstream;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function companyIndex(Request $request): JsonResponse
    {
        $companyId = $request->user()?->companyUser?->company_id;

        if (! $companyId) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $query = $this->companyWorkLogsQuery($companyId);

        $workLogs = QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('workstream_id'),
                AllowedFilter::callback('user_name', function ($query, $value) {
                    $query->whereHas('user', function ($query) use ($value) {
                        $query->where('name', 'like', '%'.$value.'%');
                    });
                }),
                AllowedFilter::callback('user_email', function ($query, $value) {
                    $query->whereHas('user', function ($query) use ($value) {
                        $query->where('email', 'like', '%'.$value.'%');
                    });
                }),
                AllowedFilter::callback('start_date', function ($query, $value) {
                    $query->whereDate('user_workstream.created_at', '>=', $value);
                }),
                AllowedFilter::callback('end_date', function ($query, $value) {
                    $query->whereDate('user_workstream.created_at', '<=', $value);
                }),
                'reference_code',
                'note',
                AllowedFilter::exact('units'),
            )
            ->allowedSorts('created_at', 'updated_at', 'units', 'reference_code', 'workstream_id', 'user_id')
            ->paginate()
            ->appends(request()->query());

        return response()->json($workLogs);
    }

    public function companySummary(Request $request): JsonResponse
    {
        $companyId = $request->user()?->companyUser?->company_id;

        if (! $companyId) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $query = $this->companyWorkLogsQuery($companyId);
        $this->applyCompanySummaryFilters($query, $request);

        $totals = (clone $query)
            ->selectRaw('COUNT(user_workstream.id) as logs_count, COALESCE(SUM(user_workstream.units), 0) as total_units, COUNT(DISTINCT user_workstream.user_id) as active_users_count')
            ->first();

        $topUser = (clone $query)
            ->select([
                'user.id',
                'user.name',
                'user.email',
                DB::raw('COALESCE(SUM(user_workstream.units), 0) as units'),
                DB::raw('COUNT(user_workstream.id) as logs_count'),
            ])
            ->join('user', 'user.id', '=', 'user_workstream.user_id')
            ->groupBy('user.id', 'user.name', 'user.email')
            ->orderByDesc('units')
            ->orderByDesc('logs_count')
            ->first();

        $topWorkstream = (clone $query)
            ->select([
                'workstream.id',
                'workstream.name',
                DB::raw('COALESCE(SUM(user_workstream.units), 0) as units'),
                DB::raw('COUNT(user_workstream.id) as logs_count'),
            ])
            ->join('workstream', 'workstream.id', '=', 'user_workstream.workstream_id')
            ->groupBy('workstream.id', 'workstream.name')
            ->orderByDesc('units')
            ->orderByDesc('logs_count')
            ->first();

        $filters = $request->input('filter', []);
        $missingUsersQuery = DB::table('user')
            ->join('company_user', 'company_user.id', '=', 'user.company_user_id')
            ->where('company_user.company_id', $companyId)
            ->where('company_user.status', 'approved')
            ->whereNotExists(function ($subQuery) use ($companyId, $filters) {
                $subQuery
                    ->selectRaw('1')
                    ->from('user_workstream')
                    ->join('workstream', 'workstream.id', '=', 'user_workstream.workstream_id')
                    ->whereColumn('user_workstream.user_id', 'user.id')
                    ->where('workstream.company_id', $companyId);

                if (! empty($filters['start_date'])) {
                    $subQuery->whereDate('user_workstream.created_at', '>=', $filters['start_date']);
                }

                if (! empty($filters['end_date'])) {
                    $subQuery->whereDate('user_workstream.created_at', '<=', $filters['end_date']);
                }

                if (! empty($filters['workstream_id'])) {
                    $subQuery->where('user_workstream.workstream_id', $filters['workstream_id']);
                }
            })
            ->when(! empty($filters['user_id']), function ($query) use ($filters) {
                $query->where('user.id', $filters['user_id']);
            })
            ->when(! empty($filters['user_name']), function ($query) use ($filters) {
                $query->where('user.name', 'like', '%'.$filters['user_name'].'%');
            })
            ->when(! empty($filters['user_email']), function ($query) use ($filters) {
                $query->where('user.email', 'like', '%'.$filters['user_email'].'%');
            });

        $missingUsersCount = $missingUsersQuery->count();

        return response()->json([
            'logs_count' => (int) ($totals->logs_count ?? 0),
            'total_units' => (int) ($totals->total_units ?? 0),
            'active_users_count' => (int) ($totals->active_users_count ?? 0),
            'missing_users_count' => $missingUsersCount,
            'top_user' => $topUser,
            'top_workstream' => $topWorkstream,
        ]);
    }

    public function companyOptions(Request $request): JsonResponse
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
            ->get(['id', 'name']);

        $users = DB::table('user')
            ->select('user.id', 'user.name', 'user.email')
            ->join('company_user', 'company_user.id', '=', 'user.company_user_id')
            ->where('company_user.company_id', $companyId)
            ->where('company_user.status', 'approved')
            ->orderBy('user.name')
            ->get();

        return response()->json([
            'workstreams' => $workstreams,
            'users' => $users,
        ]);
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

    private function companyWorkLogsQuery(int $companyId)
    {
        return UserWorkstream::query()
            ->with(['user', 'workstream'])
            ->whereHas('user.companyUser', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->whereHas('workstream', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            });
    }

    private function applyCompanySummaryFilters($query, Request $request): void
    {
        $filters = $request->input('filter', []);

        if (! empty($filters['user_id'])) {
            $query->where('user_workstream.user_id', $filters['user_id']);
        }

        if (! empty($filters['workstream_id'])) {
            $query->where('user_workstream.workstream_id', $filters['workstream_id']);
        }

        if (! empty($filters['user_name'])) {
            $query->whereHas('user', function ($query) use ($filters) {
                $query->where('name', 'like', '%'.$filters['user_name'].'%');
            });
        }

        if (! empty($filters['user_email'])) {
            $query->whereHas('user', function ($query) use ($filters) {
                $query->where('email', 'like', '%'.$filters['user_email'].'%');
            });
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('user_workstream.created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('user_workstream.created_at', '<=', $filters['end_date']);
        }

        if (! empty($filters['reference_code'])) {
            $query->where('reference_code', 'like', '%'.$filters['reference_code'].'%');
        }

        if (! empty($filters['note'])) {
            $query->where('note', 'like', '%'.$filters['note'].'%');
        }
    }
}
