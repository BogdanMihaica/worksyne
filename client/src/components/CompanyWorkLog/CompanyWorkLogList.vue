<script setup>
import { onMounted, ref } from 'vue'
import { useHttp } from '../../plugins/http'
import { useAppToast } from '../../plugins/toast'

const http = useHttp()
const toast = useAppToast()

const userId = ref('')
const userName = ref('')
const userEmail = ref('')
const workstreamId = ref('')
const startDate = ref('')
const endDate = ref('')
const referenceCode = ref('')
const note = ref('')
const filters = ref({})
const gridKey = ref(0)
const loadingSummary = ref(false)
const loadingOptions = ref(false)
const summary = ref(null)
const userOptions = ref([{ value: '', label: 'All users' }])
const workstreamOptions = ref([{ value: '', label: 'All workstreams' }])

const columns = [
  { field: 'user.name', header: 'User name' },
  { field: 'user.email', header: 'User email' },
  { field: 'workstream.name', header: 'Workstream' },
  { field: 'units', header: 'Units', sortable: true, numeric: true },
  {
    field: 'reference_code',
    header: 'Reference code',
    sortable: true,
    format: value => value || '-',
  },
  {
    field: 'note',
    header: 'Note',
    format: value => value || '-',
  },
  { field: 'created_at', header: 'Logged at', sortable: true, date: true },
  { field: 'updated_at', header: 'Updated at', sortable: true, date: true },
]

onMounted(() => {
  loadOptions()
  loadSummary()
})

async function loadOptions() {
  loadingOptions.value = true

  try {
    const { data } = await http.get('/api/company-work-logs/options')

    userOptions.value = [
      { value: '', label: 'All users' },
      ...data.users.map((user) => ({
        value: user.id,
        label: `${user.name} (${user.email})`,
      })),
    ]

    workstreamOptions.value = [
      { value: '', label: 'All workstreams' },
      ...data.workstreams.map((workstream) => ({
        value: workstream.id,
        label: workstream.name,
      })),
    ]
  } catch {
    toast({ type: 'error', message: 'Unable to load work log filters.' })
  } finally {
    loadingOptions.value = false
  }
}

async function loadSummary() {
  loadingSummary.value = true

  try {
    const { data } = await http.get('/api/company-work-logs/summary', {
      params: filters.value,
    })

    summary.value = data
  } catch {
    toast({ type: 'error', message: 'Unable to load work log summary.' })
  } finally {
    loadingSummary.value = false
  }
}

function onSearch() {
  filters.value = {
    filter: {
      user_id: userId.value,
      user_name: userName.value,
      user_email: userEmail.value,
      workstream_id: workstreamId.value,
      start_date: startDate.value,
      end_date: endDate.value,
      reference_code: referenceCode.value,
      note: note.value,
    },
  }

  gridKey.value += 1
  loadSummary()
}

function onCancel() {
  userId.value = ''
  userName.value = ''
  userEmail.value = ''
  workstreamId.value = ''
  startDate.value = ''
  endDate.value = ''
  referenceCode.value = ''
  note.value = ''
  filters.value = {}
  gridKey.value += 1
  loadSummary()
}

function formatNumber(value) {
  return Number(value || 0).toLocaleString()
}

function metricValue(value) {
  return loadingSummary.value ? '-' : formatNumber(value)
}

function topLabel(item, fallback) {
  if (loadingSummary.value) {
    return '-'
  }

  return item?.name || fallback
}

function topDetail(item) {
  if (!item) {
    return 'No matching logs'
  }

  return `${formatNumber(item.units)} units / ${formatNumber(item.logs_count)} logs`
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <app-card>
      <template #title>
        Work Logs
      </template>

      <template #content>
        <filter-layout :on-search="onSearch" :on-cancel="onCancel">
          <template #filters>
            <form-select
              v-model="userId"
              label="User"
              :options="userOptions"
              :default-option="false"
            />
            <form-select
              v-model="workstreamId"
              label="Workstream"
              :options="workstreamOptions"
              :default-option="false"
            />
            <form-date v-model="startDate" label="From" />
            <form-date v-model="endDate" label="To" />
            <form-input v-model="userName" label="User name" />
            <form-input v-model="userEmail" label="User email" />
            <form-input v-model="referenceCode" label="Reference code" />
            <form-input v-model="note" label="Note" />
          </template>
        </filter-layout>
      </template>
    </app-card>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
      <app-card>
        <template #title>
          Units
        </template>

        <template #content>
          <div class="text-2xl font-semibold text-slate-950">{{ metricValue(summary?.total_units) }}</div>
          <div class="mt-1 text-xs text-slate-500">Total work logged</div>
        </template>
      </app-card>

      <app-card>
        <template #title>
          Entries
        </template>

        <template #content>
          <div class="text-2xl font-semibold text-slate-950">{{ metricValue(summary?.logs_count) }}</div>
          <div class="mt-1 text-xs text-slate-500">Matching log rows</div>
        </template>
      </app-card>

      <app-card>
        <template #title>
          Active Users
        </template>

        <template #content>
          <div class="text-2xl font-semibold text-slate-950">{{ metricValue(summary?.active_users_count) }}</div>
          <div class="mt-1 text-xs text-slate-500">{{ metricValue(summary?.missing_users_count) }} users without logs</div>
        </template>
      </app-card>

      <app-card>
        <template #title>
          Top User
        </template>

        <template #content>
          <div class="truncate text-sm font-semibold text-slate-950">{{ topLabel(summary?.top_user, 'No user') }}</div>
          <div class="mt-1 text-xs text-slate-500">{{ topDetail(summary?.top_user) }}</div>
        </template>
      </app-card>

      <app-card>
        <template #title>
          Top Workstream
        </template>

        <template #content>
          <div class="truncate text-sm font-semibold text-slate-950">{{ topLabel(summary?.top_workstream, 'No workstream') }}</div>
          <div class="mt-1 text-xs text-slate-500">{{ topDetail(summary?.top_workstream) }}</div>
        </template>
      </app-card>
    </div>

    <app-card>
      <template #title>
        Entries
      </template>

      <template #content>
        <app-grid
          :key="gridKey"
          :columns="columns"
          url="/api/company-work-logs"
          default-sort-field="created_at"
          default-sort-order="desc"
          :filters="filters"
        />
      </template>
    </app-card>
  </div>
</template>
