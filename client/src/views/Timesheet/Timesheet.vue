<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useHttp } from '../../plugins/http'
import { useAppToast } from '../../plugins/toast'
import { authStore } from '../../stores/auth'

const http = useHttp()
const toast = useAppToast()
const isTimeoffModalOpen = ref(false)
const timeoffRequests = ref([])
const companyUsers = ref([])
const visibleRange = ref(defaultRange())
const actionTooltip = ref(null)
const actionTooltipRef = ref(null)
const selectedTimeoffRequest = ref(null)

const isCompanyAdmin = computed(() => authStore.userRole.value === 'company_admin')
const canRequestTimeoff = computed(() => {
  const companyRole = authStore.state.user?.company_user?.role || authStore.userRole.value

  return ['team_lead', 'worker'].includes(companyRole)
})

const rows = computed(() => {
  const user = authStore.state.user

  if (isCompanyAdmin.value) {
    return companyUsers.value.map((companyUser) => ({
      id: companyUser.id,
      name: companyUser.name,
      subtitle: companyUser.email,
    }))
  }

  if (!user) {
    return []
  }

  return [
    {
      id: user.id,
      name: user.name,
      subtitle: user.email,
    },
  ]
})

const events = computed(() => {
  const user = authStore.state.user

  if (!user && !isCompanyAdmin.value) {
    return []
  }

  return [
    ...timeoffRequests.value
      .filter((request) => request.status !== 'rejected')
      .map((request) => ({
        id: `timeoff-${request.id}`,
        timeoffRequestId: request.id,
        status: request.status,
        resource: request.user_id,
        start: `${toDateOnly(request.start_date)}T00:00:00`,
        end: `${addDays(request.end_date, 1)}T00:00:00`,
        text: `${request.reason || 'Timeoff request'} (Timeoff)`,
        ...eventColors(request.status),
      })),
  ]
})

onMounted(() => {
  loadTimeoffRequests()
})

onBeforeUnmount(() => {
  removeOutsideClickListener()
})

watch(
  () => authStore.state.user?.id,
  () => {
    loadTimeoffRequests()
  },
)

async function loadTimeoffRequests() {
  const user = authStore.state.user

  if (!user) {
    companyUsers.value = []
    timeoffRequests.value = []
    return
  }

  if (isCompanyAdmin.value) {
    await loadCompanyTimesheet()
    return
  }

  const { data } = await http.get('/api/timeoff-requests', {
    params: {
      filter: {
        user_id: user.id,
        range_start: visibleRange.value.start,
        range_end: visibleRange.value.end,
      },
      sort: 'start_date',
    },
  })

  timeoffRequests.value = data.data
}

async function loadCompanyTimesheet() {
  const { data } = await http.get('/api/company-timesheet', {
    params: {
      range_start: visibleRange.value.start,
      range_end: visibleRange.value.end,
    },
  })

  companyUsers.value = data.users || []
  timeoffRequests.value = data.timeoff_requests || []
}

function onRangeChange(range) {
  visibleRange.value = range
  closeActionTooltip()
  loadTimeoffRequests()
}

function onEventClick(payload) {
  const event = payload.event
  const timeoffRequest = timeoffRequests.value.find((request) => request.id === event?.timeoffRequestId)

  if (!timeoffRequest) {
    closeActionTooltip()
    return
  }

  if ((intValue(timeoffRequest.user_id)) === intValue(authStore.state.user?.id)) {
    closeActionTooltip()
    selectedTimeoffRequest.value = timeoffRequest
    isTimeoffModalOpen.value = true
    return
  }

  if (!isCompanyAdmin.value || event?.status !== 'pending') {
    closeActionTooltip()
    return
  }

  actionTooltip.value = {
    timeoffRequestId: event.timeoffRequestId,
    x: payload.x,
    y: payload.y,
  }
  addOutsideClickListener()
}

function openCreateTimeoff() {
  selectedTimeoffRequest.value = null
  isTimeoffModalOpen.value = true
}

function onTimeoffModalVisible(value) {
  isTimeoffModalOpen.value = value

  if (!value) {
    selectedTimeoffRequest.value = null
  }
}

async function updateTimeoffStatus(status) {
  if (!actionTooltip.value) {
    return
  }

  const timeoffRequestId = actionTooltip.value.timeoffRequestId
  closeActionTooltip()

  try {
    await http.patch(`/api/company-timeoff-requests/${timeoffRequestId}/status`, {
      status,
    })

    toast({ type: 'success', message: 'Timeoff request updated successfully.' })
    await loadTimeoffRequests()
  } catch (error) {
    toast({ type: 'error', message: error.response?.data?.message || 'Unable to update timeoff request.' })
  }
}

function closeActionTooltip() {
  actionTooltip.value = null
  removeOutsideClickListener()
}

function addOutsideClickListener() {
  removeOutsideClickListener()
  setTimeout(() => {
    document.addEventListener('click', onOutsideTooltipClick)
  }, 0)
}

function removeOutsideClickListener() {
  document.removeEventListener('click', onOutsideTooltipClick)
}

function onOutsideTooltipClick(event) {
  if (actionTooltipRef.value?.contains(event.target)) {
    return
  }

  closeActionTooltip()
}

function defaultRange() {
  const start = startOfWeek(new Date())
  const end = addDays(start, 6)

  return {
    start: formatDate(start),
    end,
  }
}

function toDateOnly(value) {
  return String(value).split('T')[0]
}

function addDays(value, days) {
  const dateOnly = toDateOnly(value)
  const nextDate = new Date(`${dateOnly}T00:00:00`)

  nextDate.setDate(nextDate.getDate() + days)

  const year = nextDate.getFullYear()
  const month = String(nextDate.getMonth() + 1).padStart(2, '0')
  const day = String(nextDate.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function startOfWeek(date) {
  const nextDate = new Date(date)
  const day = nextDate.getDay()
  const distance = day === 0 ? -6 : 1 - day

  nextDate.setDate(nextDate.getDate() + distance)

  return nextDate
}

function formatDate(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function intValue(value) {
  return Number.parseInt(value, 10)
}

function eventColors(status) {
  if (status === 'approved') {
    return {
      cssClass: 'worksyne-scheduler-event-approved',
      backColor: '#dcfce7',
      borderColor: '#22c55e',
      fontColor: '#166534',
    }
  }

  return {
    cssClass: 'worksyne-scheduler-event-pending',
    backColor: '#fef3c7',
    borderColor: '#f59e0b',
    fontColor: '#92400e',
  }
}
</script>

<template>
  <app-scheduler
    title="Timesheet"
    :rows="rows"
    :events="events"
    @range-change="onRangeChange"
    @event-click="onEventClick"
  >
    <template #actions>
      <form-button
        v-if="canRequestTimeoff"
        icon="plus"
        label="Request Timeoff"
        @click="openCreateTimeoff"
      />
      <form-button
        v-if="isCompanyAdmin"
        icon="plus"
        label="Add Timeoff"
        @click="openCreateTimeoff"
      />
    </template>
  </app-scheduler>

  <div
    v-if="actionTooltip"
    ref="actionTooltipRef"
    class="fixed z-50 w-58 rounded-md border border-slate-200 bg-white p-2 shadow-lg"
    :style="{ left: `${actionTooltip.x + 8}px`, top: `${actionTooltip.y + 8}px` }"
  >
    <div class="mb-2 text-xs font-semibold text-slate-700">
      Review this pending timeoff request
    </div>
    <div class="flex gap-1">
      <form-button
        label="Approve"
        icon="check"
        severity="success"
        size="sm"
        @click="updateTimeoffStatus('approved')"
      />
      <form-button
        label="Reject"
        icon="x"
        severity="danger"
        size="sm"
        @click="updateTimeoffStatus('rejected')"
      />
    </div>
  </div>

  <timeoff-request-modal
    :model-value="isTimeoffModalOpen"
    :user-id="authStore.state.user?.id"
    :status="isCompanyAdmin ? 'approved' : 'pending'"
    :title="selectedTimeoffRequest ? 'Edit Timeoff' : (isCompanyAdmin ? 'Add Timeoff' : 'Request Timeoff')"
    :submit-label="selectedTimeoffRequest ? 'Save' : (isCompanyAdmin ? 'Add' : 'Request')"
    :timeoff-request="selectedTimeoffRequest"
    @update:model-value="onTimeoffModalVisible"
    @created="loadTimeoffRequests"
    @updated="loadTimeoffRequests"
  />
</template>
