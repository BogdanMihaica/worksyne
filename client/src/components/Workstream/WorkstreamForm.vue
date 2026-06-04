<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHttp } from '../../plugins/http'
import { useAppToast } from '../../plugins/toast'
import { authStore } from '../../stores/auth'

const route = useRoute()
const router = useRouter()
const http = useHttp()
const toast = useAppToast()
const name = ref('')
const capacityModels = ref([
  { seniority: 'intern', units_per_hour: 0 },
  { seniority: 'junior', units_per_hour: 0 },
  { seniority: 'mid', units_per_hour: 0 },
  { seniority: 'senior', units_per_hour: 0 },
])
const errors = ref({})
const loading = ref(false)
const saving = ref(false)

const isEditMode = computed(() => Boolean(route.params.id))
const companyId = computed(() => authStore.state.user?.company_user?.company_id)
const title = computed(() => isEditMode.value ? 'Edit Workstream' : 'Create Workstream')

onMounted(async () => {
  loading.value = true
  await Promise.all([
    loadWorkstream(),
    loadCapacityModels(),
  ])
  loading.value = false
})

async function loadWorkstream() {
  if (!isEditMode.value) {
    return
  }

  const { data } = await http.get(`/api/workstreams/${route.params.id}`)

  name.value = data.name
}

async function loadCapacityModels() {
  if (!isEditMode.value) {
    return
  }

  const { data } = await http.get(`/api/workstreams/${route.params.id}/capacity-models`)

  capacityModels.value = data.map((item) => ({
    seniority: item.seniority,
    units_per_hour: Number(item.units_per_hour || 0),
  }))
}

async function submit() {
  saving.value = true
  errors.value = {}

  const payload = {
    name: name.value,
    company_id: companyId.value,
    capacity_models: capacityModels.value.map((item) => ({
      seniority: item.seniority,
      units_per_hour: Number(item.units_per_hour || 0),
    })),
  }

  try {
    if (isEditMode.value) {
      await http.put(`/api/workstreams/${route.params.id}`, payload)
      await http.put(`/api/workstreams/${route.params.id}/capacity-models`, {
        items: payload.capacity_models,
      })
      toast({ type: 'success', message: 'Workstream updated successfully.' })
      router.push({ name: 'workstreams' })
    } else {
      const response = await http.post('/api/workstreams', payload)
      const { data } = await http.get(`/api/workstreams/${response.data.id}`)

      toast({ type: 'success', message: 'Workstream created successfully.' })
      router.push({ name: 'workstream-edit', params: { id: data.id } })
    }
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
      {{ title }}
    </template>

    <template #content>
      <form class="flex flex-col gap-4" @submit.prevent="submit">
        <form-input v-model="name" label="Name" required size="lg" :error="errors.name" />
        <div class="overflow-hidden border border-slate-200">
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
              required
              min="0"
              step="0.01"
              size="sm"
              :error="errors[`capacity_models.${index}.units_per_hour`] || errors[`items.${index}.units_per_hour`]"
            />
          </div>
        </div>
        <form-error v-if="errors.capacity_models || errors.items" :error="errors.capacity_models || errors.items" />
        <div class="flex gap-2">
          <form-button type="submit" icon="save" label="Save" :loading="saving || loading" />
          <form-button severity="ternary" label="Cancel" @click.prevent="router.push({ name: 'workstreams' })" />
        </div>
      </form>
    </template>
  </app-card>
</template>
