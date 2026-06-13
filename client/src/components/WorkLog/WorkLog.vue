<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useHttp } from '../../plugins/http'
import { useAppToast } from '../../plugins/toast'
import { authStore } from '../../stores/auth'

const http = useHttp()
const toast = useAppToast()

const companyId = computed(() => authStore.state.user?.company_user?.company_id)
const workstreamOptions = ref([])
const gridKey = ref(0)
const loadingWorkstreams = ref(false)
const saving = ref(false)
const errors = ref({})
const logsFilters = ref({})
const editingWorkLog = ref(null)
const formSection = ref(null)

const workstreamId = ref('')
const units = ref(1)
const loggedOn = ref('')
const referenceCode = ref('')
const note = ref('')

const isEditMode = computed(() => Boolean(editingWorkLog.value?.id))
const title = computed(() => isEditMode.value ? 'Edit Work Log' : 'Add Work Log')
const submitLabel = computed(() => isEditMode.value ? 'Update' : 'Save')
const cancelLabel = computed(() => isEditMode.value ? 'Cancel Edit' : 'Clear')

const columns = [
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
  { field: 'logged_on', header: 'Work day', sortable: true, date: true },
  { field: 'created_at', header: 'Created at', date: true },
  {
    field: 'actions',
    header: 'Actions',
    type: 'actions',
    widthFit: true,
    items: ({ data }) => [
      {
        label: 'Edit',
        icon: 'pen-to-square',
        severity: 'secondary',
        onClick: () => startEdit(data),
      },
    ],
  },
]

watch(
  companyId,
  () => {
    if (companyId.value) {
      loadWorkstreams()
    }
  },
  { immediate: true },
)

onMounted(() => {
  resetForm()
})

async function loadWorkstreams() {
  if (!companyId.value) {
    workstreamOptions.value = []
    return
  }

  loadingWorkstreams.value = true

  try {
    const { data } = await http.get('/api/work-log/workstreams')
    workstreamOptions.value = data.map((item) => ({
      value: item.id,
      label: item.name,
    }))
  } catch {
    toast({ type: 'error', message: 'Unable to load workstreams.' })
  } finally {
    loadingWorkstreams.value = false
  }
}

function resetForm() {
  editingWorkLog.value = null
  workstreamId.value = ''
  units.value = 1
  loggedOn.value = new Date().toLocaleDateString('en-CA')
  referenceCode.value = ''
  note.value = ''
  errors.value = {}
}

async function startEdit(workLog) {
  editingWorkLog.value = workLog
  workstreamId.value = workLog.workstream_id || ''
  units.value = workLog.units ?? 1
  loggedOn.value = String(workLog.logged_on || '').split('T')[0]
  referenceCode.value = workLog.reference_code || ''
  note.value = workLog.note || ''
  errors.value = {}

  await nextTick()
  formSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

async function submit() {
  saving.value = true
  errors.value = {}

  const payload = {
    workstream_id: workstreamId.value,
    units: units.value,
    logged_on: loggedOn.value,
    reference_code: referenceCode.value,
    note: note.value,
  }

  try {
    if (isEditMode.value) {
      await http.put(`/api/work-log/${editingWorkLog.value.id}`, payload)
      toast({ type: 'success', message: 'Work log updated successfully.' })
    } else {
      await http.post('/api/work-log', payload)
      toast({ type: 'success', message: 'Work log saved successfully.' })
    }

    resetForm()
    gridKey.value += 1
  } catch (error) {
    toast({ type: 'error', message: error.response?.data?.message || 'Some errors occured' })

    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
  } finally {
    saving.value = false
  }
}

function cancelEdit() {
  resetForm()
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <div ref="formSection" class="scroll-mt-24">
      <app-card v-if="companyId" size="medium">
        <template #title>
          {{ title }}
        </template>

        <template #content>
          <form class="flex flex-col gap-4" @submit.prevent="submit">
            <form-select
              v-model="workstreamId"
              label="Workstream"
              required
              size="lg"
              :options="workstreamOptions"
              :default-option="false"
              :error="errors.workstream_id"
            />

            <form-input
              v-model="units"
              label="Units"
              type="number"
              required
              size="lg"
              description="Number of completed units to record for this workstream."
              :error="errors.units"
            />

            <form-date
              v-model="loggedOn"
              label="Work day"
              required
              size="lg"
              :error="errors.logged_on"
            />

            <form-input
              v-model="referenceCode"
              label="Reference code"
              size="lg"
              description="Optional ticket, case, order, or task identifier."
              :error="errors.reference_code"
            />

            <form-textarea
              v-model="note"
              label="Note"
              size="lg"
              :rows="4"
              :error="errors.note"
            />

            <div class="flex flex-wrap gap-2">
              <form-button
                type="submit"
                icon="save"
                :label="submitLabel"
                :loading="saving || loadingWorkstreams"
              />
              <form-button
                severity="ternary"
                icon="ban"
                :label="cancelLabel"
                @click.prevent="cancelEdit"
              />
            </div>
          </form>
        </template>
      </app-card>
    </div>

    <app-card v-if="companyId">
      <template #title>
        Recent logs
      </template>

      <template #content>
        <app-grid
          :key="gridKey"
          :columns="columns"
          url="/api/work-log"
          default-sort-field="logged_on"
          default-sort-order="desc"
          :filters="logsFilters"
        />
      </template>
    </app-card>
  </div>
</template>
