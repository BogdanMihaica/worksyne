<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useHttp } from '../../plugins/http'
import { useAppToast } from '../../plugins/toast'
import { authStore } from '../../stores/auth'

const http = useHttp()
const toast = useAppToast()

const workstreams = ref([])
const workstreamId = ref('')
const capacityModels = ref([])
const errors = ref({})
const loading = ref(false)
const loadingModels = ref(false)
const saving = ref(false)

const companyId = computed(() => authStore.state.user?.company_user?.company_id)
const workstreamOptions = computed(() => workstreams.value.map((workstream) => ({
  value: workstream.id,
  label: workstream.name,
})))

onMounted(async () => {
  loading.value = true
  await loadWorkstreams()
  loading.value = false
})

watch(workstreamId, () => {
  loadCapacityModels()
})

async function loadWorkstreams() {
  if (!companyId.value) {
    return
  }

  const { data } = await http.get('/api/workstreams', {
    params: {
      filter: {
        company_id: companyId.value,
      },
      sort: 'name',
    },
  })

  workstreams.value = data.data

  if (!workstreamId.value && workstreams.value.length) {
    workstreamId.value = workstreams.value[0].id
  }
}

async function loadCapacityModels() {
  if (!workstreamId.value) {
    capacityModels.value = []
    return
  }

  loadingModels.value = true
  errors.value = {}

  try {
    const { data } = await http.get(`/api/workstreams/${workstreamId.value}/capacity-models`)
    capacityModels.value = data.map((item) => ({
      ...item,
      units_per_hour: Number(item.units_per_hour || 0),
    }))
  } catch (error) {
    toast({ type: 'error', message: 'Unable to load capacity models.' })
  } finally {
    loadingModels.value = false
  }
}

async function saveCapacityModels() {
  if (!workstreamId.value) {
    return
  }

  saving.value = true
  errors.value = {}

  try {
    const { data } = await http.put(`/api/workstreams/${workstreamId.value}/capacity-models`, {
      items: capacityModels.value.map((item) => ({
        seniority: item.seniority,
        units_per_hour: Number(item.units_per_hour || 0),
      })),
    })

    capacityModels.value = data.map((item) => ({
      ...item,
      units_per_hour: Number(item.units_per_hour || 0),
    }))

    toast({ type: 'success', message: 'Capacity models updated successfully.' })
  } catch (error) {
    toast({ type: 'error', message: 'Some errors occured' })

    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
  } finally {
    saving.value = false
  }
}

function seniorityLabel(value) {
  return value.charAt(0).toUpperCase() + value.slice(1)
}
</script>

<template>
  <app-card size="medium">
    <template #title>
      Capacity Models
    </template>

    <template #content>
      <div class="flex flex-col gap-4">
        <form-select
          v-model="workstreamId"
          label="Workstream"
          required
          size="lg"
          :options="workstreamOptions"
          :default-option="false"
        />

        <div v-if="workstreamId" class="overflow-hidden border border-slate-200">
          <div class="grid grid-cols-[1fr_160px] bg-slate-50 px-3 py-2 text-xs font-semibold uppercase text-slate-500">
            <div>Seniority</div>
            <div>Units per hour</div>
          </div>

          <div
            v-for="(capacityModel, index) in capacityModels"
            :key="capacityModel.seniority"
            class="grid grid-cols-[1fr_160px] items-center gap-3 border-t border-slate-200 px-3 py-2"
          >
            <div class="text-sm font-semibold text-slate-700">{{ seniorityLabel(capacityModel.seniority) }}</div>
            <form-input
              v-model="capacityModel.units_per_hour"
              label="Units per hour"
              type="number"
              min="0"
              step="0.01"
              size="sm"
              :error="errors[`items.${index}.units_per_hour`]"
            />
          </div>
        </div>

        <div v-if="!workstreamId && !loading" class="text-sm text-slate-500">
          No workstreams are available for this company.
        </div>

        <div class="flex gap-2">
          <form-button
            label="Save"
            icon="save"
            :disabled="!workstreamId"
            :loading="saving || loading || loadingModels"
            @click="saveCapacityModels"
          />
        </div>
      </div>
    </template>
  </app-card>
</template>
