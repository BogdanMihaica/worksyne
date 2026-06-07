<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Notification;
use App\Models\UserWorkstream;
use App\Services\CompanyWorkloadForecaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function show(Request $request, CompanyWorkloadForecaster $forecaster): JsonResponse
    {
        $user = $request->user();
        $role = $user->is_admin ? 'admin' : ($user->companyUser?->role ?? 'user');
        $companyId = $user->companyUser?->company_id;

        $forecast = $role === 'company_admin' && $companyId && $this->companyHasFeature($companyId, 'dashboard-flashcards')
            ? $forecaster->forecast($companyId)
            : null;

        return response()->json([
            'total_worked_items_today' => $this->totalWorkedItemsToday($user->id, $role, $companyId),
            'unread_notifications_count' => $this->unreadNotificationsCount($user->id),
            'flashcards' => $forecast ? $this->forecastFlashcards($forecast) : [],
        ]);
    }

    private function totalWorkedItemsToday(int $userId, string $role, ?int $companyId): int
    {
        $query = UserWorkstream::query()
            ->whereDate('user_workstream.logged_on', today());

        if ($role === 'company_admin' && $companyId) {
            $query->whereHas('workstream', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            });
        } elseif ($role === 'worker') {
            $query->where('user_workstream.user_id', $userId);
        } else {
            return 0;
        }

        return (int) $query->sum('user_workstream.units');
    }

    private function unreadNotificationsCount(int $userId): int
    {
        return Notification::query()
            ->where('to_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    private function companyHasFeature(int $companyId, string $featureKey): bool
    {
        return Company::query()
            ->whereKey($companyId)
            ->whereHas('subscriptionPlan.features', fn ($query) => $query->where('key', $featureKey))
            ->exists();
    }

    private function forecastFlashcards(array $forecast): array
    {
        $workstreamRows = collect($forecast['days'] ?? [])
            ->flatMap(function (array $day) {
                return collect($day['workstreams'] ?? [])
                    ->map(fn (array $workstream) => [
                        ...$workstream,
                        'date' => $day['date'],
                        'is_weekend' => $day['is_weekend'],
                    ]);
            });

        return collect([
            $this->shortfallFlashcard($workstreamRows),
            $this->weekendDemandFlashcard(collect($forecast['days'] ?? [])),
            $this->surplusFlashcard($workstreamRows),
        ])
            ->filter()
            ->values()
            ->all();
    }

    private function shortfallFlashcard(Collection $workstreamRows): ?array
    {
        $shortfall = $workstreamRows
            ->where('gap_units', '<', 0)
            ->sortBy('gap_units')
            ->first();

        if (! $shortfall) {
            return null;
        }

        $missingUnits = abs((int) $shortfall['gap_units']);

        return [
            'type' => 'forecast_shortfall',
            'title' => 'Capacity risk',
            'message' => sprintf(
                '"%s" workstream is forecast to be short by %d units on %s.',
                $this->formatWorkstreamName($shortfall['workstream_name']),
                $missingUnits,
                $shortfall['date'],
            ),
            'severity' => 'danger',
            'metric' => $missingUnits,
        ];
    }

    private function weekendDemandFlashcard(Collection $days): ?array
    {
        $weekendDemand = $days
            ->where('is_weekend', true)
            ->sum(fn (array $day) => (int) ($day['totals']['predicted_units'] ?? 0));

        if ($weekendDemand <= 0) {
            return null;
        }

        return [
            'type' => 'weekend_demand',
            'title' => 'Weekend demand',
            'message' => sprintf('%d units are forecast for the weekend with no scheduled capacity.', $weekendDemand),
            'severity' => 'warning',
            'metric' => $weekendDemand,
        ];
    }

    private function surplusFlashcard(Collection $workstreamRows): ?array
    {
        $surplus = $workstreamRows
            ->where('gap_units', '>', 0)
            ->sortByDesc('gap_units')
            ->first();

        if (! $surplus) {
            return null;
        }

        return [
            'type' => 'forecast_surplus',
            'title' => 'Capacity buffer',
            'message' => sprintf(
                '"%s" workstream has the strongest buffer with %d available units on %s.',
                $this->formatWorkstreamName($surplus['workstream_name']),
                (int) $surplus['gap_units'],
                $surplus['date'],
            ),
            'severity' => 'success',
            'metric' => (int) $surplus['gap_units'],
        ];
    }

    private function formatWorkstreamName(string $name): string
    {
        return ucfirst(strtolower($name));
    }
}
