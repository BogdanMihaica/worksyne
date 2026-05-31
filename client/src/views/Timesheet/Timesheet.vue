<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useHttp } from '../../plugins/http'
import { authStore } from '../../stores/auth'

const http = useHttp()
const isTimeoffModalOpen = ref(false)
const timeoffRequests = ref([])

const canRequestTimeoff = computed(() => {
  const companyRole = authStore.state.user?.company_user?.role || authStore.userRole.value

  return ['team_lead', 'worker'].includes(companyRole)
})

const rows = computed(() => {
  const user = authStore.state.user

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

  if (!user) {
    return []
  }

  return [
    ...timeoffRequests.value.map((request) => ({
      id: `timeoff-${request.id}`,
      resource: user.id,
      start: `${toDateOnly(request.start_date)}T00:00:00`,
      end: `${addDays(request.end_date, 1)}T00:00:00`,
      text: request.reason || 'Timeoff request',
      backColor: '#fef3c7',
      borderColor: '#f59e0b',
      fontColor: '#92400e',
    })),
  ]
})

onMounted(() => {
  loadTimeoffRequests()
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
    timeoffRequests.value = []
    return
  }

  const { data } = await http.get('/api/timeoff-requests', {
    params: {
      filter: {
        user_id: user.id,
        status: 'pending',
      },
      sort: 'start_date',
    },
  })

  timeoffRequests.value = data.data
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
</script>

<template>
  <app-scheduler
    title="Timesheet"
    :rows="rows"
    :events="events"
  >
    <template #actions>
      <form-button
        v-if="canRequestTimeoff"
        icon="plus"
        label="Request Timeoff"
        @click="isTimeoffModalOpen = true"
      />
    </template>
  </app-scheduler>

  <timeoff-request-modal
    v-model="isTimeoffModalOpen"
    :user-id="authStore.state.user?.id"
    @created="loadTimeoffRequests"
  />
</template>
