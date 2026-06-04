<script setup>
import { computed, onMounted, ref } from 'vue'
import { useHttp } from '../../plugins/http'
import { useAppToast } from '../../plugins/toast'

const http = useHttp()
const toast = useAppToast()

const forecast = ref(null)
const loading = ref(false)

const rows = computed(() => {
  return (forecast.value?.days || []).flatMap((day) => {
    return day.workstreams.map((workstream, index) => ({
      ...workstream,
      date: day.date,
      is_weekend: day.is_weekend,
      day_totals: day.totals,
      is_first_day_row: index === 0,
      day_rowspan: day.workstreams.length,
    }))
  })
})

onMounted(() => {
  loadForecast()
})

async function loadForecast() {
  loading.value = true

  try {
    const { data } = await http.get('/api/company-forecast')

    forecast.value = data
  } catch {
    toast({ type: 'error', message: 'Unable to load workload forecast.' })
  } finally {
    loading.value = false
  }
}

function formatDate(value) {
  if (!value) {
    return '-'
  }

  return new Intl.DateTimeFormat(undefined, {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  }).format(new Date(`${value}T00:00:00`))
}

function formatNumber(value) {
  return Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })
}

function metricValue(value) {
  return loading.value ? '-' : formatNumber(value)
}

function gapClass(value) {
  if (Number(value || 0) < 0) {
    return 'font-semibold text-red-700'
  }

  return 'font-semibold text-emerald-700'
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <app-card>
      <template #title>
        Forecast
      </template>

      <template #actions>
        <button
          type="button"
          class="inline-flex h-9 items-center gap-2 rounded-md border border-slate-300 px-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="loading"
          @click="loadForecast"
        >
          <i class="pi pi-refresh text-sm" :class="{ 'pi-spin': loading }" />
          Refresh
        </button>
      </template>

      <template #content>
        <div class="grid gap-4 md:grid-cols-3">
          <div>
            <div class="text-xs font-medium uppercase text-slate-500">Predicted workload</div>
            <div class="mt-1 text-2xl font-semibold text-slate-950">{{ metricValue(forecast?.totals?.predicted_units) }}</div>
          </div>
          <div>
            <div class="text-xs font-medium uppercase text-slate-500">Available capacity</div>
            <div class="mt-1 text-2xl font-semibold text-slate-950">{{ metricValue(forecast?.totals?.available_capacity_units) }}</div>
          </div>
          <div>
            <div class="text-xs font-medium uppercase text-slate-500">Forecast gap</div>
            <div class="mt-1 text-2xl" :class="gapClass(forecast?.totals?.gap_units)">
              {{ metricValue(forecast?.totals?.gap_units) }}
            </div>
          </div>
        </div>

        <div class="mt-4 text-xs text-slate-500">
          {{ forecast?.start_date ? formatDate(forecast.start_date) : '-' }} to
          {{ forecast?.end_date ? formatDate(forecast.end_date) : '-' }}
        </div>
      </template>
    </app-card>

    <app-card>
      <template #title>
        7-Day Workload
      </template>

      <template #content>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
              <tr>
                <th class="w-44 px-3 py-2">Date</th>
                <th class="px-3 py-2">Workstream</th>
                <th class="px-3 py-2 text-right">Predicted workload</th>
                <th class="px-3 py-2 text-right">Available capacity</th>
                <th class="px-3 py-2 text-right">Gap</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-if="loading">
                <td class="px-3 py-8 text-center text-slate-500" colspan="5">Loading forecast...</td>
              </tr>
              <tr v-else-if="!rows.length">
                <td class="px-3 py-8 text-center text-slate-500" colspan="5">No workstreams found.</td>
              </tr>
              <template v-else>
                <tr
                  v-for="row in rows"
                  :key="`${row.date}-${row.workstream_id}`"
                  class="align-top"
                >
                  <td
                    v-if="row.is_first_day_row"
                    class="border-r border-slate-100 px-3 py-2"
                    :rowspan="row.day_rowspan"
                  >
                    <div class="font-semibold text-slate-950">{{ formatDate(row.date) }}</div>
                    <div class="mt-1 text-xs text-slate-500">
                      Daily gap:
                      <span :class="gapClass(row.day_totals.gap_units)">
                        {{ formatNumber(row.day_totals.gap_units) }}
                      </span>
                    </div>
                    <div
                      v-if="row.is_weekend"
                      class="mt-2 inline-flex rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
                    >
                      Weekend
                    </div>
                  </td>
                  <td class="px-3 py-2 font-medium text-slate-900">{{ row.workstream_name }}</td>
                  <td class="px-3 py-2 text-right tabular-nums text-slate-700">{{ formatNumber(row.predicted_units) }}</td>
                  <td class="px-3 py-2 text-right tabular-nums text-slate-700">{{ formatNumber(row.available_capacity_units) }}</td>
                  <td class="px-3 py-2 text-right tabular-nums" :class="gapClass(row.gap_units)">
                    {{ formatNumber(row.gap_units) }}
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </template>
    </app-card>
  </div>
</template>
