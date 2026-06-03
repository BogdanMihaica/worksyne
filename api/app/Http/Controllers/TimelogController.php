<?php

namespace App\Http\Controllers;

use App\Models\Timelog;
use App\Models\TimelogBreak;

class TimelogController extends Controller
{
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
