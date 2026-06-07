<?php

namespace App\Services;

use App\Models\CapacityModel;
use App\Models\CompanyUserSeniority;
use App\Models\Timelog;
use App\Models\TimeoffRequest;
use App\Models\UserWorkstream;
use App\Models\Workstream;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompanyWorkloadForecaster
{
    public function forecast(int $companyId, ?CarbonInterface $requestDate = null): array
    {
        $requestDate = Carbon::parse($requestDate ?? now())->startOfDay();
        $forecastStart = $requestDate->copy()->addDay();
        $forecastEnd = $forecastStart->copy()->addDays(6);
        $historyStart = $requestDate->copy()->subMonthsNoOverflow(3)->startOfDay();
        $historyEnd = $requestDate->copy()->subDay()->endOfDay();
        $historyDaysByWeekday = $this->historyDaysByWeekday($historyStart, $historyEnd);

        $workstreams = Workstream::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $demandByWorkstreamAndWeekday = $this->demandByWorkstreamAndWeekday(
            $companyId,
            $historyStart,
            $historyEnd,
            $historyDaysByWeekday,
        );

        $userHours = $this->averageUserHours($companyId, $historyStart, $historyEnd);
        $timeOffByUserAndDate = $this->approvedTimeOffByUserAndDate($companyId, $forecastStart, $forecastEnd);
        $senioritiesByWorkstream = $this->senioritiesByWorkstream($companyId);
        $capacityModels = $this->capacityModels($companyId);

        $days = collect(CarbonPeriod::create($forecastStart, $forecastEnd))
            ->map(function (CarbonInterface $date) use (
                $workstreams,
                $demandByWorkstreamAndWeekday,
                $userHours,
                $timeOffByUserAndDate,
                $senioritiesByWorkstream,
                $capacityModels,
            ) {
                return $this->forecastDay(
                    $date,
                    $workstreams,
                    $demandByWorkstreamAndWeekday,
                    $userHours,
                    $timeOffByUserAndDate,
                    $senioritiesByWorkstream,
                    $capacityModels,
                );
            })
            ->values();

        return [
            'start_date' => $forecastStart->toDateString(),
            'end_date' => $forecastEnd->toDateString(),
            'history_start_date' => $historyStart->toDateString(),
            'history_end_date' => $historyEnd->toDateString(),
            'totals' => [
                'predicted_units' => (int) $days->sum('totals.predicted_units'),
                'available_capacity_units' => (int) $days->sum('totals.available_capacity_units'),
                'gap_units' => (int) $days->sum('totals.gap_units'),
            ],
            'days' => $days->all(),
        ];
    }

    private function forecastDay(
        CarbonInterface $date,
        Collection $workstreams,
        Collection $demandByWorkstreamAndWeekday,
        Collection $userHours,
        Collection $timeOffByUserAndDate,
        Collection $senioritiesByWorkstream,
        Collection $capacityModels,
    ): array {
        $dateKey = $date->toDateString();
        $isWeekend = $date->isWeekend();
        $weekday = $date->dayOfWeekIso;

        $forecastWorkstreams = $workstreams
            ->map(function (Workstream $workstream) use (
                $weekday,
                $dateKey,
                $isWeekend,
                $demandByWorkstreamAndWeekday,
                $userHours,
                $timeOffByUserAndDate,
                $senioritiesByWorkstream,
                $capacityModels,
            ) {
                $predictedUnits = ($demandByWorkstreamAndWeekday->get($workstream->id)?->get($weekday) ?? 0);
                $availableCapacity = $isWeekend ? 0 : $this->workstreamCapacity(
                    $workstream->id,
                    $dateKey,
                    $userHours,
                    $timeOffByUserAndDate,
                    $senioritiesByWorkstream,
                    $capacityModels,
                );
                $predictedUnits = floor($predictedUnits);
                $availableCapacity = floor($availableCapacity);

                return [
                    'workstream_id' => $workstream->id,
                    'workstream_name' => $workstream->name,
                    'predicted_units' => $predictedUnits,
                    'available_capacity_units' => $availableCapacity,
                    'gap_units' => $availableCapacity - $predictedUnits,
                ];
            })
            ->values();

        return [
            'date' => $dateKey,
            'is_weekend' => $isWeekend,
            'workstreams' => $forecastWorkstreams->all(),
            'totals' => [
                'predicted_units' => $forecastWorkstreams->sum('predicted_units'),
                'available_capacity_units' => $forecastWorkstreams->sum('available_capacity_units'),
                'gap_units' => $forecastWorkstreams->sum('gap_units'),
            ],
        ];
    }

    private function workstreamCapacity(
        int $workstreamId,
        string $date,
        Collection $userHours,
        Collection $timeOffByUserAndDate,
        Collection $senioritiesByWorkstream,
        Collection $capacityModels,
    ): float {
        return $senioritiesByWorkstream
            ->get($workstreamId, collect())
            ->sum(function (CompanyUserSeniority $seniority) use ($workstreamId, $date, $userHours, $timeOffByUserAndDate, $capacityModels) {
                if ($timeOffByUserAndDate->get($seniority->user_id, collect())->contains($date)) {
                    return 0;
                }

                $unitsPerHour = ($capacityModels->get($workstreamId)?->get($seniority->seniority) ?? 0);

                return ($userHours->get($seniority->user_id, 8) * $unitsPerHour);
            });
    }

    private function demandByWorkstreamAndWeekday(
        int $companyId,
        CarbonInterface $historyStart,
        CarbonInterface $historyEnd,
        Collection $historyDaysByWeekday,
    ): Collection {
        $totals = UserWorkstream::query()
            ->select([
                'user_workstream.workstream_id',
                DB::raw('DAYOFWEEK(user_workstream.logged_on) as weekday'),
                DB::raw('COALESCE(SUM(user_workstream.units), 0) as units'),
            ])
            ->join('workstream', 'workstream.id', '=', 'user_workstream.workstream_id')
            ->where('workstream.company_id', $companyId)
            ->whereBetween('user_workstream.logged_on', [$historyStart->toDateString(), $historyEnd->toDateString()])
            ->groupBy('user_workstream.workstream_id', DB::raw('DAYOFWEEK(user_workstream.logged_on)'))
            ->get();
        return $totals
            ->groupBy('workstream_id')
            ->map(function (Collection $workstreamRows) use ($historyDaysByWeekday) {
                return $workstreamRows
                    ->mapWithKeys(function ($row) use ($historyDaysByWeekday) {
                        $isoWeekday = $row->weekday === 1 ? 7 : $row->weekday - 1;
                        $historyDays = max($historyDaysByWeekday->get($isoWeekday, 0), 1);
                        return [$isoWeekday => $row->units / $historyDays];
                    });
            });
    }

    private function averageUserHours(int $companyId, CarbonInterface $historyStart, CarbonInterface $historyEnd): Collection
    {
        $timelogs = Timelog::query()
            ->with(['breaks' => function ($query) {
                $query->whereNotNull('end_time');
            }])
            ->whereNotNull('end_time')
            ->whereBetween('start_time', [$historyStart, $historyEnd])
            ->whereHas('user.companyUser', function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->where('status', 'approved');
            })
            ->get();

        return $timelogs
            ->groupBy('user_id')
            ->map(function (Collection $userTimelogs) {
                return $userTimelogs->avg(function (Timelog $timelog) {
                    $loggedMinutes = $timelog->start_time->diffInMinutes($timelog->end_time);
                    $breakMinutes = $timelog->breaks->sum(function ($break) {
                        return $break->start_time->diffInMinutes($break->end_time);
                    });

                    return max($loggedMinutes - $breakMinutes, 0) / 60;
                });
            });
    }

    private function approvedTimeOffByUserAndDate(int $companyId, CarbonInterface $forecastStart, CarbonInterface $forecastEnd): Collection
    {
        $query = TimeoffRequest::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $forecastEnd)
            ->whereDate('end_date', '>=', $forecastStart)
            ->whereHas('user.companyUser', function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->where('status', 'approved');
            })
            ->get(['user_id', 'start_date', 'end_date'])
            ->groupBy('user_id')
            ->map(function (Collection $requests) use ($forecastStart, $forecastEnd) {
                return $requests
                    ->flatMap(function (TimeoffRequest $request) use ($forecastStart, $forecastEnd) {
                        $start = $request->start_date->greaterThan($forecastStart) ? $request->start_date : $forecastStart;
                        $end = $request->end_date->lessThan($forecastEnd) ? $request->end_date : $forecastEnd;

                        return collect(CarbonPeriod::create($start, $end))
                            ->map(fn(CarbonInterface $date) => $date->toDateString());
                    })
                    ->unique()
                    ->values();
            });
            
        return $query;
    }

    private function senioritiesByWorkstream(int $companyId): Collection
    {
        return CompanyUserSeniority::query()
            ->where('company_user_seniority.company_id', $companyId)
            ->whereHas('user.companyUser', function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->where('status', 'approved');
            })
            ->get(['id', 'company_id', 'user_id', 'workstream_id', 'seniority'])
            ->groupBy('workstream_id');
    }

    private function capacityModels(int $companyId): Collection
    {
        return CapacityModel::query()
            ->where('company_id', $companyId)
            ->get(['workstream_id', 'seniority', 'units_per_hour'])
            ->groupBy('workstream_id')
            ->map(function (Collection $models) {
                return $models->mapWithKeys(fn (CapacityModel $model) => [
                    $model->seniority => (float) $model->units_per_hour,
                ]);
            });
    }

    private function historyDaysByWeekday(CarbonInterface $historyStart, CarbonInterface $historyEnd): Collection
    {
        if ($historyStart->greaterThan($historyEnd)) {
            return collect();
        }

        return collect(CarbonPeriod::create($historyStart, $historyEnd))
            ->countBy(fn (CarbonInterface $date) => $date->dayOfWeekIso);
    }
}
