<script setup>
import { onMounted, ref } from 'vue'
import { useHttp } from '../../plugins/http'

const http = useHttp()
const overview = ref(null)
const loading = ref(false)

onMounted(() => {
  loadOverview()
})

async function loadOverview() {
  loading.value = true

  const { data } = await http.get('/api/company-overview')

  overview.value = data
  loading.value = false
}

function formatDate(value) {
  return value ? value.split('T')[0] : '-'
}
</script>

<template>
  <app-card>
    <template #title>
      Company
    </template>

    <template #content>
      <div v-if="loading" class="text-sm text-slate-500">Loading...</div>

      <div v-if="overview" class="grid gap-6">
        <div class="grid gap-4 lg:grid-cols-4">
          <div class="rounded-md border border-slate-200 bg-white p-4">
            <div class="text-sm text-slate-500">Company</div>
            <div class="mt-2 text-sm font-semibold text-slate-950">{{ overview.company.name }}</div>
          </div>
          <div class="rounded-md border border-slate-200 bg-white p-4">
            <div class="text-sm text-slate-500">Owner</div>
            <div class="mt-2 text-sm font-semibold text-slate-950">{{ overview.company.owner.email }}</div>
          </div>
          <div class="rounded-md border border-slate-200 bg-white p-4">
            <div class="text-sm text-slate-500">Users</div>
            <div class="mt-2 text-sm font-semibold text-slate-950">{{ overview.company.users_count }}</div>
          </div>
          <div class="rounded-md border border-slate-200 bg-white p-4">
            <div class="text-sm text-slate-500">Workstreams</div>
            <div class="mt-2 text-sm font-semibold text-slate-950">{{ overview.company.workstreams_count }}</div>
          </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
          <div class="rounded-md border border-slate-200 bg-white p-4">
            <div class="text-sm font-semibold text-slate-600">Active subscription</div>
            <div v-if="overview.active_subscription" class="mt-4 grid gap-2 text-sm text-slate-600">
              <div class="text-xl font-semibold text-slate-950">
                {{ overview.active_subscription.subscription_plan.name }}
              </div>
              <div>Started: {{ formatDate(overview.active_subscription.starts_at) }}</div>
              <div>Status: {{ overview.active_subscription.status }}</div>
            </div>
            <div v-else class="mt-4 text-sm text-slate-500">No active subscription.</div>
          </div>

          <div class="rounded-md border border-slate-200 bg-white p-4">
            <div class="text-sm font-semibold text-slate-600">Top workers this week</div>
            <div class="mt-4 grid gap-3">
              <div
                v-for="worker in overview.top_workers"
                :key="worker.id"
                class="grid gap-1 rounded-md bg-slate-50 p-3 sm:grid-cols-[1fr_auto] sm:items-center"
              >
                <div>
                  <div class="font-medium text-slate-950">{{ worker.name }}</div>
                  <div class="text-sm text-slate-500">{{ worker.email }}</div>
                </div>
                <div class="text-sm font-semibold text-slate-700">
                  {{ worker.workstream_count }} workstreams / {{ worker.units || 0 }} units
                </div>
              </div>
              <div v-if="!overview.top_workers.length" class="text-sm text-slate-500">
                No workstream activity this week.
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </app-card>
</template>
