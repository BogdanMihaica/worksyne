<?php

namespace App\Http\Controllers;

use App\Models\TimeoffRequest;
use App\Models\User;
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

        $timeoffRequest->update($attributes);

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

        $timeoffRequest->update($attributes);

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
}
