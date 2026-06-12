<script setup>
import { ref } from 'vue'
import { Column, DataTable } from 'primevue'

const userName = ref('')
const startTime = ref('')
const endTime = ref('')
const createdAt = ref('')
const filters = ref({})

const columns = [
  { field: 'user.name', header: 'User name' },
  { field: 'start_time', header: 'Start time', sortable: true, date: true },
  {
    field: 'end_time',
    header: 'End time',
    sortable: true,
    format: value => formatTimestamp(value),
  },
  {
    field: 'total_seconds',
    header: 'Total work time',
    format: value => formatDuration(value),
  },
  {
    field: 'total_break_seconds',
    header: 'Total break time',
    format: value => formatDuration(value),
  },
  { field: 'created_at', header: 'Created at', sortable: true, date: true },
]

function onSearch() {
  filters.value = {
    filter: {
      user_name: userName.value,
      start_time: startTime.value,
      end_time: endTime.value,
      created_at: createdAt.value,
    },
  }
}

function onCancel() {
  userName.value = ''
  startTime.value = ''
  endTime.value = ''
  createdAt.value = ''
  filters.value = {}
}

function formatTimestamp(value) {
  return value ? value.split('.')[0].replace('T', ' ') : '-'
}

function formatDuration(value) {
  const totalSeconds = Math.max(0, Number(value || 0))
  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  const seconds = totalSeconds % 60

  return [hours, minutes, seconds]
    .map(part => String(part).padStart(2, '0'))
    .join(':')
}
</script>

<template>
  <app-card>
    <template #title>
      Time Logs
    </template>

    <template #content>
      <filter-layout :on-search="onSearch" :on-cancel="onCancel" class="mb-6">
        <template #filters>
          <form-input v-model="userName" label="User name" />
          <form-date v-model="startTime" label="Start date" />
          <form-date v-model="endTime" label="End date" />
          <form-date v-model="createdAt" label="Created date" />
        </template>
      </filter-layout>

      <app-grid
        :columns="columns"
        url="/api/company-timelogs"
        default-sort-field="start_time"
        default-sort-order="desc"
        :filters="filters"
        expandable
      >
        <template #expansion="{ data }">
          <div class="border-t border-slate-100 bg-slate-50 p-4">
            <div class="mb-3 text-sm font-semibold text-slate-800">Breaks</div>

            <DataTable
              :value="data.breaks || []"
              size="small"
              class="overflow-hidden rounded-md border border-slate-200 bg-white"
            >
              <Column header="User name">
                <template #body>
                  {{ data.user?.name || '-' }}
                </template>
              </Column>
              <Column field="start_time" header="Start time">
                <template #body="{ data: breakItem }">
                  {{ formatTimestamp(breakItem.start_time) }}
                </template>
              </Column>
              <Column field="end_time" header="End time">
                <template #body="{ data: breakItem }">
                  {{ formatTimestamp(breakItem.end_time) }}
                </template>
              </Column>
              <Column field="total_seconds" header="Total time">
                <template #body="{ data: breakItem }">
                  {{ formatDuration(breakItem.total_seconds) }}
                </template>
              </Column>
              <Column field="note" header="Reason">
                <template #body="{ data: breakItem }">
                  {{ breakItem.note || '-' }}
                </template>
              </Column>

              <template #empty>
                <div class="py-4 text-center text-sm text-slate-500">No breaks recorded.</div>
              </template>
            </DataTable>
          </div>
        </template>
      </app-grid>
    </template>
  </app-card>
</template>
