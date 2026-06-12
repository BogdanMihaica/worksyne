<?php

namespace App\Http\Controllers;

use App\Models\Timelog;
use App\Models\TimelogBreak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TimelogController extends Controller
{
    public function companyIndex(Request $request): JsonResponse
    {
        $companyId = $request->user()?->companyUser?->company_id;

        if (! $companyId) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $query = Timelog::query()
            ->with([
                'user:id,name,email',
                'breaks' => fn ($query) => $query->orderBy('start_time'),
            ])
            ->whereHas('user.companyUser', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            });

        $timelogs = QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::callback('user_name', function ($query, $value) {
                    $query->whereHas('user', function ($query) use ($value) {
                        $query->where('name', 'like', '%'.$value.'%');
                    });
                }),
                AllowedFilter::callback('start_time', function ($query, $value) {
                    $query->whereDate('timelog.start_time', $value);
                }),
                AllowedFilter::callback('end_time', function ($query, $value) {
                    $query->whereDate('timelog.end_time', $value);
                }),
                AllowedFilter::callback('created_at', function ($query, $value) {
                    $query->whereDate('timelog.created_at', $value);
                }),
            )
            ->allowedSorts('start_time', 'end_time', 'created_at')
            ->paginate()
            ->appends($request->query());

        $now = now();

        $timelogs->getCollection()->each(function (Timelog $timelog) use ($now) {
            $sessionStart = $timelog->start_time->getTimestamp();
            $sessionEnd = ($timelog->end_time ?? $now)->getTimestamp();
            $breakSeconds = 0;

            $timelog->breaks->each(function (TimelogBreak $break) use ($now, $sessionStart, $sessionEnd, &$breakSeconds) {
                $breakStart = max($break->start_time->getTimestamp(), $sessionStart);
                $breakEnd = min(($break->end_time ?? $now)->getTimestamp(), $sessionEnd);
                $duration = max(0, $breakEnd - $breakStart);

                $break->setAttribute('total_seconds', $duration);
                $breakSeconds += $duration;
            });

            $timelog->setAttribute('total_break_seconds', $breakSeconds);
            $timelog->setAttribute('total_seconds', max(0, $sessionEnd - $sessionStart - $breakSeconds));
        });

        return response()->json($timelogs);
    }

    public function status()
    {
        return response()->json($this->activeState());
    }

    public function start()
    {
        $activeTimelog = $this->activeTimelog();

        if ($activeTimelog) {
            return response()->json($this->activeState());
        }

        Timelog::query()->create([
            'user_id' => request()->user()->id,
            'start_time' => now(),
            'end_time' => null,
        ]);

        return response()->json($this->activeState(), 201);
    }

    public function stop()
    {
        $activeTimelog = $this->activeTimelog();

        if (! $activeTimelog) {
            return response()->json($this->activeState());
        }

        $activeBreak = $this->activeBreak($activeTimelog);

        if ($activeBreak) {
            $activeBreak->update([
                'end_time' => now(),
            ]);
        }

        $activeTimelog->update([
            'end_time' => now(),
        ]);

        return response()->json($this->activeState());
    }

    public function startBreak()
    {
        $activeTimelog = $this->activeTimelog();

        if (! $activeTimelog) {
            return response()->json([
                'message' => 'Start work before starting a break.',
            ], 422);
        }

        if ($this->activeBreak($activeTimelog)) {
            return response()->json($this->activeState());
        }

        $attributes = request()->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        TimelogBreak::query()->create([
            'timelog_id' => $activeTimelog->id,
            'note' => $attributes['note'] ?? null,
            'start_time' => now(),
            'end_time' => null,
        ]);

        return response()->json($this->activeState(), 201);
    }

    public function resume()
    {
        $activeTimelog = $this->activeTimelog();

        if (! $activeTimelog) {
            return response()->json($this->activeState());
        }

        $activeBreak = $this->activeBreak($activeTimelog);

        if ($activeBreak) {
            $activeBreak->update([
                'end_time' => now(),
            ]);
        }

        return response()->json($this->activeState());
    }

    private function activeState()
    {
        $activeTimelog = $this->activeTimelog();

        if (! $activeTimelog) {
            return [
                'timelog' => null,
                'active_break' => null,
                'server_time' => now(),
            ];
        }

        $activeTimelog->load('breaks');

        return [
            'timelog' => $activeTimelog,
            'active_break' => $this->activeBreak($activeTimelog),
            'server_time' => now(),
        ];
    }

    private function activeTimelog()
    {
        $query = Timelog::query()
            ->where('user_id', request()->user()->id)
            ->whereNull('end_time')
            ->latest('start_time');
        return $query->first();
    }

    private function activeBreak($timelog)
    {
        $query = TimelogBreak::query()
            ->where('timelog_id', $timelog->id)
            ->whereNull('end_time')
            ->latest('start_time');

        return $query->first();
    }
}
