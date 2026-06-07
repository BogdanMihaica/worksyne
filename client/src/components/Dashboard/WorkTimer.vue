<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import Button from 'primevue/button'
import { useHttp } from '../../plugins/http'
import { useAppToast } from '../../plugins/toast'
import { authStore } from '../../stores/auth'

const http = useHttp()
const toast = useAppToast()

const timelog = ref(null)
const activeBreak = ref(null)
const loading = ref(false)
const breakOpen = ref(false)
const breakNote = ref('')
const tick = ref(Date.now())
const serverOffset = ref(0)
let intervalId = null

const companyRole = computed(() => authStore.state.user?.company_user?.role || '')
const shouldShow = computed(() => (
  authStore.hasFeature('time-logging') &&
  ['company_admin', 'worker'].includes(companyRole.value)
))
const isWorking = computed(() => Boolean(timelog.value?.id))
const isOnBreak = computed(() => Boolean(activeBreak.value?.id))
const elapsedSeconds = computed(() => {
  if (!timelog.value?.start_time) {
    return 0
  }

  const now = tick.value + serverOffset.value
  const startedAt = timestamp(timelog.value.start_time)
  const breakSeconds = (timelog.value.breaks || []).reduce((total, item) => {
    const breakStart = timestamp(item.start_time)
    const breakEnd = item.end_time ? timestamp(item.end_time) : now

    return total + Math.max(0, breakEnd - breakStart)
  }, 0)

  return Math.max(0, Math.floor((now - startedAt - breakSeconds) / 1000))
})
const formattedElapsed = computed(() => {
  const hours = Math.floor(elapsedSeconds.value / 3600)
  const minutes = Math.floor((elapsedSeconds.value % 3600) / 60)
  const seconds = elapsedSeconds.value % 60

  return [hours, minutes, seconds].map(value => String(value).padStart(2, '0')).join(':')
})

watch(
  shouldShow,
  (value) => {
    if (value) {
      loadStatus()
      startTicker()
      return
    }

    stopTicker()
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  stopTicker()
})

async function loadStatus() {
  if (!shouldShow.value) {
    return
  }

  loading.value = true

  try {
    const { data } = await http.get('/api/timelog/status')
    setState(data)
  } catch {
    toast({ type: 'error', message: 'Unable to load work timer.' })
  } finally {
    loading.value = false
  }
}

async function startWork() {
  loading.value = true

  try {
    const { data } = await http.post('/api/timelog/start')
    setState(data)
  } catch (error) {
    toast({ type: 'error', message: error.response?.data?.message || 'Unable to start work.' })
  } finally {
    loading.value = false
  }
}

async function stopWork() {
  loading.value = true

  try {
    const { data } = await http.patch('/api/timelog/stop')
    setState(data)
    breakOpen.value = false
    breakNote.value = ''
  } catch (error) {
    toast({ type: 'error', message: error.response?.data?.message || 'Unable to stop work.' })
  } finally {
    loading.value = false
  }
}

async function proceedBreak() {
  loading.value = true

  try {
    const { data } = await http.post('/api/timelog/break', {
      note: breakNote.value,
    })
    setState(data)
    breakOpen.value = false
    breakNote.value = ''
  } catch (error) {
    toast({ type: 'error', message: error.response?.data?.message || 'Unable to start break.' })
  } finally {
    loading.value = false
  }
}

async function resumeWork() {
  loading.value = true

  try {
    const { data } = await http.patch('/api/timelog/resume')
    setState(data)
  } catch (error) {
    toast({ type: 'error', message: error.response?.data?.message || 'Unable to resume work.' })
  } finally {
    loading.value = false
  }
}

function setState(data) {
  timelog.value = data.timelog
  activeBreak.value = data.active_break

  if (data.server_time) {
    serverOffset.value = timestamp(data.server_time) - Date.now()
  }

  tick.value = Date.now()
}

function startTicker() {
  if (intervalId) {
    return
  }

  intervalId = window.setInterval(() => {
    tick.value = Date.now()
  }, 1000)
}

function stopTicker() {
  if (!intervalId) {
    return
  }

  window.clearInterval(intervalId)
  intervalId = null
}

function timestamp(value) {
  return new Date(value).getTime()
}
</script>

<template>
  <div v-if="shouldShow" class="relative flex flex-wrap items-center justify-end gap-2">
    <Button
      v-if="!isWorking"
      type="button"
      label="Start work"
      icon="pi pi-play"
      size="small"
      :loading="loading"
      @click="startWork"
    />

    <template v-else>
      <div class="min-w-28 rounded-sm border border-slate-200 bg-slate-50 px-3 py-1.5 text-center font-mono text-sm text-slate-800">
        {{ formattedElapsed }}
      </div>

      <Button
        type="button"
        label="Stop work"
        icon="pi pi-stop"
        size="small"
        severity="danger"
        :loading="loading"
        @click="stopWork"
      />

      <Button
        v-if="!isOnBreak"
        type="button"
        label="Break"
        icon="pi pi-pause"
        size="small"
        severity="secondary"
        :disabled="loading"
        @click="breakOpen = !breakOpen"
      />

      <Button
        v-else
        type="button"
        label="Resume"
        icon="pi pi-play"
        size="small"
        severity="success"
        :loading="loading"
        @click="resumeWork"
      />
    </template>

    <div
      v-if="breakOpen"
      class="absolute right-0 top-11 z-20 w-72 rounded-md border border-slate-200 bg-white p-4 shadow-lg"
    >
      <label class="block">
        <span class="mb-1 block text-[13px] font-semibold text-slate-700">Note/reason</span>
        <textarea
          v-model="breakNote"
          rows="3"
          class="w-full border border-slate-200 p-2 text-sm text-slate-700 outline-none focus:border-brand-500"
        />
      </label>

      <div class="mt-3 flex justify-end gap-2">
        <Button
          type="button"
          label="Cancel"
          size="small"
          severity="secondary"
          outlined
          @click="breakOpen = false"
        />
        <Button
          type="button"
          label="Proceed"
          size="small"
          :loading="loading"
          @click="proceedBreak"
        />
      </div>
    </div>
  </div>
</template>
