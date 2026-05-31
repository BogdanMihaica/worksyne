<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Order;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $period = $request->string('period', 'year')->toString();
        $year = (int) $request->integer('year', now()->year);
        $month = (int) $request->integer('month', now()->month);
        $week = (int) $request->integer('week', now()->weekOfYear);

        [$start, $end, $labels, $format] = $this->range($period, $year, $month, $week);

        return response()->json([
            'period' => $period,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'labels' => $labels,
            'series' => [
                'companies' => $this->countSeries(Company::query(), $start, $end, $labels, $format),
                'orders' => $this->countSeries(Order::query(), $start, $end, $labels, $format),
                'income' => $this->incomeSeries($start, $end, $labels, $format),
            ],
        ]);
    }

    private function range(string $period, int $year, int $month, int $week): array
    {
        if ($period === 'week') {
            $start = CarbonImmutable::now()->setISODate($year, $week)->startOfWeek();
            $end = $start->endOfWeek();

            return [$start, $end, $this->dailyLabels($start, $end), '%Y-%m-%d'];
        }

        if ($period === 'month') {
            $start = CarbonImmutable::create($year, $month, 1)->startOfMonth();
            $end = $start->endOfMonth();

            return [$start, $end, $this->dailyLabels($start, $end), '%Y-%m-%d'];
        }

        $start = CarbonImmutable::create($year, 1, 1)->startOfYear();
        $end = $start->endOfYear();

        return [$start, $end, $this->monthlyLabels($start), '%Y-%m'];
    }

    private function dailyLabels(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $labels = [];

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $labels[] = $date->toDateString();
        }

        return $labels;
    }

    private function monthlyLabels(CarbonImmutable $start): array
    {
        $labels = [];

        for ($month = 0; $month < 12; $month++) {
            $labels[] = $start->addMonths($month)->format('Y-m');
        }

        return $labels;
    }

    private function countSeries($query, CarbonImmutable $start, CarbonImmutable $end, array $labels, string $format): array
    {
        $values = $query
            ->selectRaw("DATE_FORMAT(created_at, ?) as label, COUNT(*) as total", [$format])
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->groupBy('label')
            ->pluck('total', 'label');

        return $this->fillLabels($labels, $values);
    }

    private function incomeSeries(CarbonImmutable $start, CarbonImmutable $end, array $labels, string $format): array
    {
        $values = Payment::query()
            ->selectRaw("DATE_FORMAT(paid_at, ?) as label, SUM(amount) as total", [$format])
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start->startOfDay(), $end->endOfDay()])
            ->groupBy('label')
            ->pluck('total', 'label');

        return $this->fillLabels($labels, $values);
    }

    private function fillLabels(array $labels, $values): array
    {
        return collect($labels)
            ->mapWithKeys(fn (string $label) => [$label => (float) ($values[$label] ?? 0)])
            ->all();
    }
}
