<script setup>
import { computed, onMounted, ref } from 'vue'
import { authStore } from '../../stores/auth'
import { useHttp } from '../../plugins/http'
import { useAppToast } from '../../plugins/toast'

const http = useHttp()
const toast = useAppToast()

const dashboard = ref(null)
const loading = ref(false)

const isCompanyAdmin = computed(() => authStore.state.user?.role === 'company_admin')
const flashcards = computed(() => dashboard.value?.flashcards || [])

onMounted(() => {
  loadDashboard()
})

async function loadDashboard() {
  loading.value = true

  try {
    const { data } = await http.get('/api/dashboard')

    dashboard.value = data
  } catch {
    toast({ type: 'error', message: 'Unable to load dashboard.' })
  } finally {
    loading.value = false
  }
}

function formatNumber(value) {
  return Number(value || 0).toLocaleString()
}

function metricValue(value) {
  return loading.value ? '-' : formatNumber(value)
}

function flashcardClasses(severity) {
  const classes = {
    danger: 'border-red-200 bg-red-50 text-red-950',
    warning: 'border-amber-200 bg-amber-50 text-amber-950',
    success: 'border-emerald-200 bg-emerald-50 text-emerald-950',
  }

  return classes[severity] || 'border-slate-200 bg-slate-50 text-slate-950'
}

function flashcardIcon(severity) {
  const icons = {
    danger: 'pi pi-exclamation-triangle text-red-600',
    warning: 'pi pi-clock text-amber-600',
    success: 'pi pi-check-circle text-emerald-600',
  }

  return icons[severity] || 'pi pi-info-circle text-slate-600'
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-950">Dashboard</h1>
        <p class="text-sm text-slate-500">Today at a glance</p>
      </div>

      <form-button
        type="button"
        label="Refresh"
        icon="pi pi-refresh"
        severity="secondary"
        :disabled="loading"
        @click="loadDashboard"
      />
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <app-card>
        <template #title>
          Total worked items today
        </template>

        <template #content>
          <div class="text-3xl font-semibold text-slate-950">
            {{ metricValue(dashboard?.total_worked_items_today) }}
          </div>
          <div class="mt-1 text-sm text-slate-500">
            {{ isCompanyAdmin ? 'Units logged by the company today' : 'Units logged by you today' }}
          </div>
        </template>
      </app-card>

      <app-card>
        <template #title>
          Unread notifications
        </template>

        <template #content>
          <div class="text-3xl font-semibold text-slate-950">
            {{ metricValue(dashboard?.unread_notifications_count) }}
          </div>
          <div class="mt-1 text-sm text-slate-500">Notifications waiting for you</div>
        </template>
      </app-card>
    </div>

    <app-card v-if="isCompanyAdmin">
      <template #title>
        Forecast signals
      </template>

      <template #content>
        <div v-if="loading" class="text-sm text-slate-500">Loading forecast signals...</div>

        <div v-else-if="flashcards.length" class="grid gap-4 lg:grid-cols-3">
          <div
            v-for="card in flashcards"
            :key="card.type"
            class="rounded-lg border p-4 shadow-sm"
            :class="flashcardClasses(card.severity)"
          >
            <div class="flex items-start gap-3">
              <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white/70">
                <i :class="flashcardIcon(card.severity)" />
              </div>
              <div class="min-w-0">
                <div class="text-sm font-semibold">{{ card.title }}</div>
                <div class="mt-1 text-sm leading-6 opacity-85">{{ card.message }}</div>
                <div class="mt-3 text-2xl font-semibold tabular-nums">{{ formatNumber(card.metric) }}</div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
          No forecast alerts right now.
        </div>
      </template>
    </app-card>
  </div>
</template>
