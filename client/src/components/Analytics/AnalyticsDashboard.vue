<script setup>
import { computed, onMounted, ref } from 'vue'
import { useHttp } from '../../plugins/http'

const http = useHttp()
const now = new Date()
const period = ref('year')
const year = ref(now.getFullYear())
const month = ref(now.getMonth() + 1)
const week = ref(getWeekNumber(now))
const analytics = ref(null)
const loading = ref(false)

const periodOptions = [
  { value: 'week', label: 'Week' },
  { value: 'month', label: 'Month' },
  { value: 'year', label: 'Year' },
]
const monthOptions = [
  { value: 1, label: 'January' },
  { value: 2, label: 'February' },
  { value: 3, label: 'March' },
  { value: 4, label: 'April' },
  { value: 5, label: 'May' },
  { value: 6, label: 'June' },
  { value: 7, label: 'July' },
  { value: 8, label: 'August' },
  { value: 9, label: 'September' },
  { value: 10, label: 'October' },
  { value: 11, label: 'November' },
  { value: 12, label: 'December' },
]

const charts = computed(() => {
  if (!analytics.value) {
    return []
  }

  return [
    {
      title: 'Companies Added',
      value: total(analytics.value.series.companies),
      rows: chartRows(analytics.value.series.companies),
      color: 'bg-cyan-600',
      formatter: formatNumber,
    },
    {
      title: 'Orders Made',
      value: total(analytics.value.series.orders),
      rows: chartRows(analytics.value.series.orders),
      color: 'bg-slate-700',
      formatter: formatNumber,
    },
    {
      title: 'Income',
      value: total(analytics.value.series.income),
      rows: chartRows(analytics.value.series.income),
      color: 'bg-emerald-600',
      formatter: formatCurrency,
    },
  ]
})

onMounted(() => {
  loadAnalytics()
})

async function loadAnalytics() {
  loading.value = true

  const { data } = await http.get('/api/analytics', {
    params: {
      period: period.value,
      year: year.value,
      month: month.value,
      week: week.value,
    },
  })

  analytics.value = data
  loading.value = false
}

function chartRows(series) {
  const entries = Object.entries(series)
  const max = Math.max(...entries.map(([, value]) => Number(value)), 1)

  return entries.map(([label, value]) => ({
    label: formatLabel(label),
    value: Number(value),
    width: `${Math.max((Number(value) / max) * 100, Number(value) > 0 ? 4 : 0)}%`,
  }))
}

function total(series) {
  return Object.values(series).reduce((sum, value) => sum + Number(value), 0)
}

function formatLabel(label) {
  if (period.value === 'year') {
    const date = new Date(`${label}-01T00:00:00`)

    return date.toLocaleDateString(undefined, { month: 'short' })
  }

  const date = new Date(`${label}T00:00:00`)

  return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}

function formatNumber(value) {
  return new Intl.NumberFormat().format(value)
}

function formatCurrency(value) {
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
  }).format(value)
}

function getWeekNumber(date) {
  const current = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()))
  const day = current.getUTCDay() || 7

  current.setUTCDate(current.getUTCDate() + 4 - day)

  const yearStart = new Date(Date.UTC(current.getUTCFullYear(), 0, 1))

  return Math.ceil((((current - yearStart) / 86400000) + 1) / 7)
}
</script>

<template>
  <app-card>
    <template #title>
      Analytics
    </template>

    <template #content>
      <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end gap-3">
          <form-select
            v-model="period"
            label="Range"
            :options="periodOptions"
            :default-option="false"
          />

          <form-input
            v-model="year"
            label="Year"
            type="number"
          />

          <form-select
            v-if="period === 'month'"
            v-model="month"
            label="Month"
            :options="monthOptions"
            :default-option="false"
          />

          <form-input
            v-if="period === 'week'"
            v-model="week"
            label="Week"
            type="number"
          />

          <form-button
            label="Apply"
            icon="magnifying-glass"
            :loading="loading"
            @click="loadAnalytics"
          />
        </div>

        <div v-if="analytics" class="text-sm text-slate-500">
          {{ analytics.start }} to {{ analytics.end }}
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
          <div
            v-for="chart in charts"
            :key="chart.title"
            class="rounded-lg border border-slate-200 bg-white p-4"
          >
            <div class="mb-4 flex items-start justify-between gap-3">
              <div>
                <div class="text-sm font-semibold text-slate-600">{{ chart.title }}</div>
                <div class="mt-1 text-2xl font-semibold text-slate-950">{{ chart.formatter(chart.value) }}</div>
              </div>
            </div>

            <div class="flex flex-col gap-2">
              <div
                v-for="row in chart.rows"
                :key="row.label"
                class="grid grid-cols-[64px_1fr_64px] items-center gap-2 text-xs text-slate-600"
              >
                <div>{{ row.label }}</div>
                <div class="h-6 rounded bg-slate-100">
                  <div
                    class="h-6 rounded"
                    :class="chart.color"
                    :style="{ width: row.width }"
                  />
                </div>
                <div class="text-right font-medium text-slate-800">{{ chart.formatter(row.value) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </app-card>
</template>
