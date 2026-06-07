<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\TimeoffRequest;
use App\Models\Timelog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                    AllowedFilter::callback('range_start', function ($query, $value) {
                        $query->whereDate('end_date', '>=', $value);
                    }),
                    AllowedFilter::callback('range_end', function ($query, $value) {
                        $query->whereDate('start_date', '<=', $value);
                    }),
                    AllowedFilter::exact('status'),
                )
                ->allowedSorts('start_date', 'end_date', 'status', 'created_at')
                ->paginate()
                ->appends(request()->query())
        );
    }

    public function companyIndex(Request $request): JsonResponse
    {
        $companyId = $request->user()?->companyUser?->company_id;

        if (! $companyId) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $query = TimeoffRequest::query()
            ->with('user')
            ->whereHas('user.companyUser', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            });

        $timeoffRequests = QueryBuilder::for($query)
            ->allowedFilters(
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
                AllowedFilter::callback('range_start', function ($query, $value) {
                    $query->whereDate('end_date', '>=', $value);
                }),
                AllowedFilter::callback('range_end', function ($query, $value) {
                    $query->whereDate('start_date', '<=', $value);
                }),
                AllowedFilter::exact('status'),
            )
            ->allowedSorts('start_date', 'end_date', 'status', 'created_at')
            ->paginate()
            ->appends(request()->query());

        return response()->json($timeoffRequests);
    }

    public function companyUpdateStatus(Request $request, TimeoffRequest $timeoffRequest): JsonResponse
    {
        $companyId = $request->user()?->companyUser?->company_id;

        if (! $companyId || $timeoffRequest->user?->companyUser?->company_id !== $companyId) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $attributes = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        if ($attributes['status'] === 'approved' && $this->hasApprovedOverlap($timeoffRequest->toArray(), $timeoffRequest->id)) {
            return response()->json([
                'message' => 'This timeoff overlaps an approved timeoff for the selected user.',
                'errors' => [
                    'status' => ['This timeoff overlaps an approved timeoff for the selected user.'],
                ],
            ], 422);
        }

        $previousStatus = $timeoffRequest->status;

        $timeoffRequest->update($attributes);

        if ($previousStatus !== $attributes['status']) {
            Notification::notify(
                $timeoffRequest->user_id,
                sprintf(
                    'Your timeoff request from %s to %s was %s.',
                    $this->dateOnly($timeoffRequest->start_date),
                    $this->dateOnly($timeoffRequest->end_date),
                    $attributes['status'],
                ),
                $request->user()->id,
            );
        }

        return response()->json($timeoffRequest->load('user'));
    }

    public function companyTimesheet(Request $request): JsonResponse
    {
        $companyId = $request->user()?->companyUser?->company_id;

        if (! $companyId) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $attributes = $request->validate([
            'range_start' => ['required', 'date'],
            'range_end' => ['required', 'date', 'after_or_equal:range_start'],
        ]);

        $users = User::query()
            ->with('companyUser')
            ->whereHas('companyUser', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->orderBy('name')
            ->get();

        $timeoffRequests = TimeoffRequest::query()
            ->with('user')
            ->where('status', '!=', 'rejected')
            ->whereDate('end_date', '>=', $attributes['range_start'])
            ->whereDate('start_date', '<=', $attributes['range_end'])
            ->whereHas('user.companyUser', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'users' => $users,
            'timeoff_requests' => $timeoffRequests,
            'worked_times' => $this->workedTimesForUsers($users, $attributes),
        ]);
    }

    public function workedTimes(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyUser = $user?->companyUser;

        if (! $companyUser?->company_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $attributes = $request->validate([
            'range_start' => ['required', 'date'],
            'range_end' => ['required', 'date', 'after_or_equal:range_start'],
        ]);

        $users = $companyUser->role === 'company_admin'
            ? User::query()
                ->whereHas('companyUser', fn ($query) => $query->where('company_id', $companyUser->company_id))
                ->orderBy('name')
                ->get()
            : collect([$user]);

        return response()->json([
            'worked_times' => $this->workedTimesForUsers($users, $attributes),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $attributes = $this->validatedAttributes($request, $this->storeRules());
        $user = $request->user();
        $companyUser = $user?->companyUser;
        $companyId = $companyUser?->company_id;
        $isCompanyAdmin = $companyUser?->role === 'company_admin';

        if ((int) $attributes['user_id'] !== (int) $user?->id) {
            return response()->json([
                'message' => 'You can only add timeoff for yourself.',
                'errors' => [
                    'user_id' => ['You can only add timeoff for yourself.'],
                ],
            ], 422);
        }

        if (! $isCompanyAdmin) {
            $attributes['status'] = 'pending';
        }

        if ($companyId && ! $this->userBelongsToCompany((int) $attributes['user_id'], $companyId)) {
            return response()->json([
                'message' => 'The selected user is not available for your company.',
                'errors' => [
                    'user_id' => ['The selected user is not available for your company.'],
                ],
            ], 422);
        }

        if ($this->hasApprovedOverlap($attributes)) {
            return response()->json([
                'message' => 'This timeoff overlaps an approved timeoff for the selected user.',
                'errors' => [
                    'start_date' => ['This timeoff overlaps an approved timeoff for the selected user.'],
                    'end_date' => ['This timeoff overlaps an approved timeoff for the selected user.'],
                ],
            ], 422);
        }

        $timeoffRequest = TimeoffRequest::query()->create($attributes);

        if (! $isCompanyAdmin && $companyId) {
            $this->notifyCompanyAdmins(
                $companyId,
                sprintf(
                    '%s requested timeoff from %s to %s.',
                    $user->name,
                    $this->dateOnly($timeoffRequest->start_date),
                    $this->dateOnly($timeoffRequest->end_date),
                ),
                $user->id,
            );
        }

        return response()->json($timeoffRequest, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $timeoffRequest = $this->findModel($id);
        $user = $request->user();
        $companyUser = $user?->companyUser;
        $isCompanyAdmin = $companyUser?->role === 'company_admin';

        if ((int) $timeoffRequest->user_id !== (int) $user?->id) {
            return response()->json([
                'message' => 'You can only update your own timeoff.',
            ], 403);
        }

        $attributes = $this->validatedAttributes($request, $this->updateRules($timeoffRequest));
        $attributes['user_id'] = $timeoffRequest->user_id;
        $hasChanges = $this->hasTimeoffChanges($timeoffRequest, $attributes);
        $attributes['status'] = $isCompanyAdmin || ! $hasChanges ? $timeoffRequest->status : 'pending';

        $overlapAttributes = array_merge($timeoffRequest->toArray(), $attributes);

        if ($this->hasApprovedOverlap($overlapAttributes, $timeoffRequest->id)) {
            return response()->json([
                'message' => 'This timeoff overlaps an approved timeoff for the selected user.',
                'errors' => [
                    'start_date' => ['This timeoff overlaps an approved timeoff for the selected user.'],
                    'end_date' => ['This timeoff overlaps an approved timeoff for the selected user.'],
                ],
            ], 422);
        }

        $wasApproved = $timeoffRequest->status === 'approved';

        $timeoffRequest->update($attributes);

        if (! $isCompanyAdmin && $wasApproved && $hasChanges && $companyUser?->company_id) {
            $this->notifyCompanyAdmins(
                $companyUser->company_id,
                sprintf(
                    '%s updated approved timeoff from %s to %s.',
                    $user->name,
                    $this->dateOnly($timeoffRequest->start_date),
                    $this->dateOnly($timeoffRequest->end_date),
                ),
                $user->id,
            );
        }

        return response()->json($timeoffRequest);
    }

    protected function storeRules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:user,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'timezone' => ['nullable', 'timezone'],
            'reason' => ['required', 'string', 'max:2000'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'approved', 'rejected'])],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after_or_equal:start_date'],
            'timezone' => ['sometimes', 'nullable', 'timezone'],
            'reason' => ['sometimes', 'required', 'string', 'max:2000'],
        ];
    }

    private function userBelongsToCompany(int $userId, int $companyId): bool
    {
        return User::query()
            ->whereKey($userId)
            ->whereHas('companyUser', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->exists();
    }

    private function hasApprovedOverlap(array $attributes, ?int $ignoreId = null): bool
    {
        $query = TimeoffRequest::query()
            ->where('user_id', $attributes['user_id'])
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $attributes['end_date'])
            ->whereDate('end_date', '>=', $attributes['start_date']);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    private function hasTimeoffChanges(TimeoffRequest $timeoffRequest, array $attributes): bool
    {
        foreach (['start_date', 'end_date', 'timezone', 'reason'] as $field) {
            if (array_key_exists($field, $attributes) && $this->normalizedValue($timeoffRequest->{$field}) !== $this->normalizedValue($attributes[$field])) {
                return true;
            }
        }

        return false;
    }

    private function normalizedValue($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return (string) ($value ?? '');
    }

    private function notifyCompanyAdmins($companyId, $message, ?int $fromId = null)
    {
        User::query()
            ->whereHas('companyUser', function ($query) use ($companyId) {
                $query
                    ->where('company_id', $companyId)
                    ->where('role', 'company_admin')
                    ->where('status', 'approved');
            })
            ->pluck('id')
            ->each(function ($userId) use ($message, $fromId) {
                Notification::notify($userId, $message, $fromId);
            });
    }

    private function dateOnly($value)
    {
        return $this->normalizedValue($value);
    }

    private function dailyWorkedTimes($users, $timelogs, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): array
    {
        $now = CarbonImmutable::now();

        return collect(CarbonPeriod::create($rangeStart->startOfDay(), $rangeEnd->startOfDay()))
            ->flatMap(function ($date) use ($users, $timelogs, $now) {
                $dayStart = CarbonImmutable::instance($date)->startOfDay();
                $dayEnd = $dayStart->addDay();

                return $users->map(function (User $user) use ($timelogs, $dayStart, $dayEnd, $now) {
                    $seconds = collect($timelogs->get($user->id, []))
                        ->sum(fn (Timelog $timelog) => $this->workedSecondsForDay($timelog, $dayStart, $dayEnd, $now));

                    return [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'date' => $dayStart->toDateString(),
                        'seconds' => $seconds,
                    ];
                });
            })
            ->values()
            ->all();
    }

    private function workedTimesForUsers($users, array $attributes): array
    {
        $rangeStart = CarbonImmutable::parse($attributes['range_start'])->startOfDay();
        $rangeEnd = CarbonImmutable::parse($attributes['range_end'])->endOfDay();
        $timelogs = Timelog::query()
            ->with('breaks')
            ->whereIn('user_id', $users->pluck('id'))
            ->where('start_time', '<=', $rangeEnd)
            ->where(function ($query) use ($rangeStart) {
                $query
                    ->whereNull('end_time')
                    ->orWhere('end_time', '>=', $rangeStart);
            })
            ->get()
            ->groupBy('user_id');

        return $this->dailyWorkedTimes($users, $timelogs, $rangeStart, $rangeEnd);
    }

    private function workedSecondsForDay(
        Timelog $timelog,
        CarbonImmutable $dayStart,
        CarbonImmutable $dayEnd,
        CarbonImmutable $now,
    ): int {
        $sessionStart = max($timelog->start_time->getTimestamp(), $dayStart->getTimestamp());
        $sessionEnd = min(
            ($timelog->end_time ?? $now)->getTimestamp(),
            $dayEnd->getTimestamp(),
            $now->getTimestamp(),
        );

        if ($sessionEnd <= $sessionStart) {
            return 0;
        }

        $breakSeconds = $timelog->breaks->sum(function ($break) use ($sessionStart, $sessionEnd, $now) {
            $breakStart = max($break->start_time->getTimestamp(), $sessionStart);
            $breakEnd = min(
                ($break->end_time ?? $now)->getTimestamp(),
                $sessionEnd,
                $now->getTimestamp(),
            );

            return max(0, $breakEnd - $breakStart);
        });

        return max(0, $sessionEnd - $sessionStart - $breakSeconds);
    }
}
