<script setup>
import { computed, ref } from 'vue'
import { authStore } from '../../stores/auth'

const isTimeoffModalOpen = ref(false)
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
    {
      id: 'mock-timesheet-1',
      resource: user.id,
      start: '2026-05-23T00:00:00',
      end: '2026-05-24T00:00:00',
      text: 'Mock timesheet event',
    },
  ]
})
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
  />
</template>
