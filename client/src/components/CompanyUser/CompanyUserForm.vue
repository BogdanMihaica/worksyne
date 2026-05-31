<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHttp } from '../../plugins/http'
import { useStrings } from '../../plugins/strings'
import { useAppToast } from '../../plugins/toast'
import { authStore } from '../../stores/auth'

const route = useRoute()
const router = useRouter()
const http = useHttp()
const strings = useStrings()
const toast = useAppToast()

const name = ref('')
const email = ref('')
const password = ref('')
const externalId = ref('')
const role = ref('worker')
const status = ref('approved')
const isBlocked = ref(false)
const workstreams = ref([])
const seniorities = ref({})
const errors = ref({})
const seniorityErrors = ref({})
const loading = ref(false)
const saving = ref(false)
const savingSeniorities = ref(false)

const isEditMode = computed(() => Boolean(route.params.id))
const companyId = computed(() => authStore.state.user?.company_user?.company_id)
const title = computed(() => isEditMode.value ? 'Edit Company User' : 'Create Company User')
const roleOptions = [
  { value: 'company_admin', label: 'Company admin' },
  { value: 'team_lead', label: 'Team lead' },
  { value: 'worker', label: 'Worker' },
]
const statusOptions = [
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
]
const seniorityOptions = [
  { value: '', label: '-' },
  { value: 'intern', label: 'Intern' },
  { value: 'junior', label: 'Junior' },
  { value: 'mid', label: 'Mid' },
  { value: 'senior', label: 'Senior' },
]

onMounted(async () => {
  loading.value = true

  await Promise.all([
    loadUser(),
    loadWorkstreams(),
    loadSeniorities(),
  ])

  loading.value = false
})

async function loadUser() {
  if (!isEditMode.value) {
    return
  }

  const { data } = await http.get(`/api/users/${route.params.id}`)

  name.value = data.name
  email.value = data.email
  isBlocked.value = data.is_blocked
  externalId.value = data.company_user?.external_id || ''
  role.value = data.company_user?.role || 'worker'
  status.value = data.company_user?.status || 'approved'
}

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
}

async function loadSeniorities() {
  if (!isEditMode.value || !companyId.value) {
    return
  }

  const { data } = await http.get('/api/company-user-seniorities', {
    params: {
      filter: {
        company_id: companyId.value,
        user_id: route.params.id,
      },
    },
  })

  seniorities.value = data.data.reduce((items, item) => ({
    ...items,
    [item.workstream_id]: item.seniority,
  }), {})
}

async function submit() {
  saving.value = true
  errors.value = {}

  const payload = {
    name: name.value,
    email: email.value,
    password: password.value || null,
    is_admin: false,
    is_blocked: isBlocked.value,
    company_user: {
      company_id: companyId.value,
      external_id: externalId.value || null,
      role: role.value,
      status: status.value,
    },
  }

  try {
    if (isEditMode.value) {
      await http.put(`/api/users/${route.params.id}`, payload)
      toast({ type: 'success', message: 'Company user updated successfully.' })
      router.push({ name: 'company-users' })
    } else {
      const response = await http.post('/api/users', payload)
      const { data } = await http.get(`/api/users/${response.data.id}`)

      toast({ type: 'success', message: 'Company user created successfully.' })
      router.push({ name: 'company-user-edit', params: { id: data.id } })
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

async function saveSeniorities() {
  if (!isEditMode.value) {
    toast({ type: 'error', message: 'Create the company user before setting workstreams.' })
    return
  }

  savingSeniorities.value = true
  seniorityErrors.value = {}

  const items = Object.entries(seniorities.value)
    .filter(([, seniority]) => seniority)
    .map(([workstreamId, seniority]) => ({
      workstream_id: Number(workstreamId),
      seniority,
    }))

  try {
    await http.put(`/api/company-users/${route.params.id}/seniorities`, {
      items,
    })

    toast({ type: 'success', message: 'Workstream seniorities updated successfully.' })
  } catch (error) {
    toast({ type: 'error', message: 'Some errors occured' })

    if (error.response?.status === 422) {
      seniorityErrors.value = error.response.data.errors || {}
    }
  } finally {
    savingSeniorities.value = false
  }
}

function cancelSeniorities() {
  loadSeniorities()
}
</script>

<template>
  <div class="grid gap-4 xl:grid-cols-2 xl:*:min-w-0">
    <app-card size="medium">
      <template #title>
        {{ title }}
      </template>

      <template #content>
        <form class="flex flex-col gap-4" @submit.prevent="submit">
          <form-input v-model="name" label="Name" required size="lg" :error="errors.name" />
          <form-input v-model="email" label="Email" required size="lg" :error="errors.email" />
          <form-input
            v-model="externalId"
            label="External ID"
            size="lg"
            :error="errors['company_user.external_id']"
          />
          <form-input
            v-model="password"
            label="Password"
            type="password"
            :required="!isEditMode"
            size="lg"
            :error="errors.password"
          />
          <form-select
            v-model="role"
            label="Role"
            required
            size="lg"
            :options="roleOptions"
            :default-option="false"
            :error="errors['company_user.role']"
          />
          <form-select
            v-model="status"
            label="Status"
            required
            size="lg"
            :options="statusOptions"
            :default-option="false"
            :error="errors['company_user.status']"
          />
          <form-input
            v-model="isBlocked"
            label="Blocked"
            secondary-label="User is blocked"
            type="checkbox"
            :error="errors.is_blocked"
          />

          <div class="flex gap-2">
            <form-button type="submit" icon="save" label="Save" :loading="saving || loading" />
            <form-button severity="ternary" label="Cancel" @click.prevent="router.push({ name: 'company-users' })" />
          </div>
        </form>
      </template>
    </app-card>

    <app-card v-if="isEditMode" size="fit">
      <template #title>
        Workstream Seniorities
      </template>

      <template #content>
        <div class="flex flex-col gap-4">
          <div
            v-for="workstream in workstreams"
            :key="workstream.id"
            class="grid min-w-0 gap-2 sm:w-fit sm:grid-cols-[220px_220px] sm:items-end"
          >
            <div class="min-w-0">
              <div class="text-sm font-semibold text-slate-700">{{ strings.upperCase(workstream.name) }}</div>
              <div class="text-xs text-slate-400">Set this user's seniority for this workstream.</div>
            </div>
            <form-select
              v-model="seniorities[workstream.id]"
              label="Seniority"
              size="lg"
              :options="seniorityOptions"
              :default-option="false"
              :error="seniorityErrors.items"
            />
          </div>

          <div v-if="!workstreams.length" class="text-sm text-slate-500">
            No workstreams are available for this company.
          </div>

          <div class="flex gap-2">
            <form-button
              icon="save"
              label="Save"
              :loading="savingSeniorities || loading"
              @click="saveSeniorities"
            />
            <form-button
              severity="ternary"
              label="Cancel"
              @click="cancelSeniorities"
            />
          </div>
        </div>
      </template>
    </app-card>
  </div>
</template>
