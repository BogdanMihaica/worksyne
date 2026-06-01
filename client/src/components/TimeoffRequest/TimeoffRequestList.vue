<script setup>
import { ref } from 'vue'
import { useHttp } from '../../plugins/http'
import { useAppToast } from '../../plugins/toast'

const http = useHttp()
const toast = useAppToast()

const userName = ref('')
const userEmail = ref('')
const status = ref('')
const filters = ref({})
const gridKey = ref(0)
const confirmation = ref(null)
const confirming = ref(false)
const statusOptions = [
  { value: '', label: '-' },
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
]

const columns = [
  { field: 'user.name', header: 'User name' },
  { field: 'user.email', header: 'User email' },
  { field: 'start_date', header: 'Start date', sortable: true, date: true },
  { field: 'end_date', header: 'End date', sortable: true, date: true },
  { field: 'created_at', header: 'Created at', sortable: true, date: true },
  {
    field: 'status',
    header: 'Status',
    sortable: true,
    severity: ({ data }) => statusSeverity(data.status),
  },
  {
    field: 'actions',
    header: 'Actions',
    type: 'actions',
    widthFit: true,
    items: ({ data }) => data.status === 'pending'
      ? [
          {
            label: 'Approve',
            icon: 'check',
            severity: 'success',
            onClick: () => openConfirmation(data, 'approved'),
          },
          {
            label: 'Reject',
            icon: 'x',
            severity: 'danger',
            onClick: () => openConfirmation(data, 'rejected'),
          },
        ]
      : [],
  },
]

function onSearch() {
  filters.value = {
    filter: {
      user_name: userName.value,
      user_email: userEmail.value,
      status: status.value,
    },
  }
}

function onCancel() {
  userName.value = ''
  userEmail.value = ''
  status.value = ''
  filters.value = {}
}

function openConfirmation(timeoffRequest, nextStatus) {
  confirmation.value = {
    timeoffRequest,
    nextStatus,
  }
}

function closeConfirmation() {
  confirmation.value = null
}

async function confirmStatusUpdate() {
  if (!confirmation.value) {
    return
  }

  confirming.value = true
  const { timeoffRequest, nextStatus } = confirmation.value

  try {
    const { data } = await http.patch(`/api/company-timeoff-requests/${timeoffRequest.id}/status`, {
      status: nextStatus,
    })

    timeoffRequest.status = data.status
    gridKey.value += 1
    closeConfirmation()
    toast({ type: 'success', message: 'Timeoff request updated successfully.' })
  } catch (error) {
    toast({ type: 'error', message: error.response?.data?.message || 'Unable to update timeoff request.' })
  } finally {
    confirming.value = false
  }
}

function statusSeverity(value) {
  const severityMap = {
    approved: 'success',
    rejected: 'danger',
    pending: 'warning',
  }

  return severityMap[value] || 'info'
}
</script>

<template>
  <app-card>
    <template #title>
      Timeoff Requests
    </template>

    <template #content>
      <filter-layout :on-search="onSearch" :on-cancel="onCancel" class="mb-6">
        <template #filters>
          <form-input v-model="userName" label="User name" />
          <form-input v-model="userEmail" label="User email" />
          <form-select
            v-model="status"
            label="Status"
            :options="statusOptions"
            :default-option="false"
          />
        </template>
      </filter-layout>

      <app-grid
        :key="gridKey"
        :columns="columns"
        url="/api/company-timeoff-requests"
        default-sort-field="created_at"
        default-sort-order="desc"
        :filters="filters"
      />
    </template>
  </app-card>

  <confirm-dialog
    :model-value="Boolean(confirmation)"
    title="Confirm timeoff action"
    message="This action cannot be undone"
    :confirm-label="confirmation?.nextStatus === 'approved' ? 'Approve' : 'Reject'"
    :confirm-severity="confirmation?.nextStatus === 'approved' ? 'success' : 'danger'"
    :loading="confirming"
    @update:model-value="val => !val && closeConfirmation()"
    @confirm="confirmStatusUpdate"
  />
</template>
