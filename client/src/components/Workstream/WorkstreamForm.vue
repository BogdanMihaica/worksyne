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
const errors = ref({})
const loading = ref(false)
const saving = ref(false)

const isEditMode = computed(() => Boolean(route.params.id))
const companyId = computed(() => authStore.state.user?.company_user?.company_id)
const title = computed(() => isEditMode.value ? 'Edit Workstream' : 'Create Workstream')

onMounted(async () => {
  loading.value = true
  await loadWorkstream()
  loading.value = false
})

async function loadWorkstream() {
  if (!isEditMode.value) {
    return
  }

  const { data } = await http.get(`/api/workstreams/${route.params.id}`)

  name.value = data.name
}

async function submit() {
  saving.value = true
  errors.value = {}

  const payload = {
    name: name.value,
    company_id: companyId.value,
  }

  try {
    if (isEditMode.value) {
      await http.put(`/api/workstreams/${route.params.id}`, payload)
    } else {
      await http.post('/api/workstreams', payload)
    }

    toast({ type: 'success', message: isEditMode.value ? 'Workstream updated successfully.' : 'Workstream created successfully.' })
    router.push({ name: 'workstreams' })
  } catch (error) {
    toast({ type: 'error', message: 'Some errors occured' })

    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
  } finally {
    saving.value = false
  }
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
        <div class="flex gap-2">
          <form-button type="submit" icon="save" label="Save" :loading="saving || loading" />
          <form-button severity="ternary" label="Cancel" @click.prevent="router.push({ name: 'workstreams' })" />
        </div>
      </form>
    </template>
  </app-card>
</template>
