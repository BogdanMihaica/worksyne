<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyNotificationController extends Controller
{
    public function recipients(Request $request): JsonResponse
    {
        $companyId = $request->user()?->companyUser?->company_id;

        if (! $companyId) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $query = (string) $request->query('q', '');

        $users = $this->companyUsersQuery($companyId)
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($builder) use ($query) {
                    $builder
                        ->where('user.name', 'like', '%'.$query.'%')
                        ->orWhere('user.email', 'like', '%'.$query.'%');
                });
            })
            ->orderBy('user.name')
            ->limit(20)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'label' => sprintf('%s (%s)', $user->name, $user->email),
            ]);

        return response()->json($users);
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
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $userIds = collect($attributes['user_ids'])
            ->map(fn ($userId) => (int) $userId)
            ->unique()
            ->values();

        $validUserIds = $this->companyUsersQuery($companyId)
            ->whereIn('user.id', $userIds)
            ->pluck('user.id')
            ->map(fn ($userId) => (int) $userId)
            ->values();

        if ($validUserIds->count() !== $userIds->count()) {
            throw ValidationException::withMessages([
                'user_ids' => ['Select only approved users from your company.'],
            ]);
        }

        DB::transaction(function () use ($request, $validUserIds, $attributes) {
            $validUserIds->each(fn (int $userId) => Notification::notify($userId, $attributes['message'], $request->user()->id));
        });

        return response()->json([
            'sent_count' => $validUserIds->count(),
        ], 201);
    }

    private function companyUsersQuery(int $companyId)
    {
        return User::query()
            ->select('user.id', 'user.name', 'user.email')
            ->join('company_user', 'company_user.id', '=', 'user.company_user_id')
            ->where('company_user.company_id', $companyId)
            ->where('company_user.status', 'approved');
    }
}
