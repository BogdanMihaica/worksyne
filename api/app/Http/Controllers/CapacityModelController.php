<?php

namespace App\Http\Controllers;

use App\Models\CapacityModel;
use App\Models\Workstream;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class CapacityModelController extends Controller
{
    public function forWorkstream(Request $request, Workstream $workstream): JsonResponse
    {
        $companyId = $request->user()?->companyUser?->company_id;

        if (! $companyId || $workstream->company_id !== $companyId) {
            return response()->json([
                'message' => 'This workstream is not available for your company.',
            ], 403);
        }

        return response()->json($this->modelsForWorkstream($companyId, $workstream->id));
    }

    public function updateForWorkstream(Request $request, Workstream $workstream): JsonResponse
    {
        $companyId = $request->user()?->companyUser?->company_id;

        if (! $companyId || $workstream->company_id !== $companyId) {
            return response()->json([
                'message' => 'This workstream is not available for your company.',
            ], 403);
        }

        $attributes = $request->validate([
            'items' => ['required', 'array', 'size:'.count(CapacityModel::SENIORITIES)],
            'items.*.seniority' => ['required', 'distinct', Rule::in(CapacityModel::SENIORITIES)],
            'items.*.units_per_hour' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $this->assertAllSenioritiesPresent($attributes['items']);

        DB::transaction(function () use ($attributes, $companyId, $workstream) {
            foreach ($attributes['items'] as $item) {
                CapacityModel::query()->updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'workstream_id' => $workstream->id,
                        'seniority' => $item['seniority'],
                    ],
                    [
                        'units_per_hour' => $item['units_per_hour'],
                    ],
                );
            }
        });

        return response()->json($this->modelsForWorkstream($companyId, $workstream->id));
    }

    private function modelsForWorkstream(int $companyId, int $workstreamId): array
    {
        $capacityModels = CapacityModel::query()
            ->where('company_id', $companyId)
            ->where('workstream_id', $workstreamId)
            ->get()
            ->keyBy('seniority');

        return collect(CapacityModel::SENIORITIES)
            ->map(function (string $seniority) use ($capacityModels, $companyId, $workstreamId) {
                $capacityModel = $capacityModels->get($seniority);

                return [
                    'id' => $capacityModel?->id,
                    'company_id' => $companyId,
                    'workstream_id' => $workstreamId,
                    'seniority' => $seniority,
                    'units_per_hour' => $capacityModel?->units_per_hour ?? '0.00',
                ];
            })
            ->values()
            ->all();
    }

    private function assertAllSenioritiesPresent(array $items): void
    {
        $seniorities = collect($items)->pluck('seniority')->sort()->values()->all();
        $expected = collect(CapacityModel::SENIORITIES)->sort()->values()->all();

        if ($seniorities !== $expected) {
            throw ValidationException::withMessages([
                'items' => ['Capacity models must be provided for every seniority.'],
            ]);
        }
    }
}
