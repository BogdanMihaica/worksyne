<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHttp } from '../../plugins/http'
import { useAppToast } from '../../plugins/toast'

const route = useRoute()
const router = useRouter()
const http = useHttp()
const toast = useAppToast()

const name = ref('')
const email = ref('')
const password = ref('')
const isAdmin = ref(false)
const isBlocked = ref(false)
const companyId = ref('')
const companyUserExternalId = ref('')
const companyUserRole = ref('')
const companyUserStatus = ref('pending')
const companyOptions = ref([])
const errors = ref({})
const loading = ref(false)
const saving = ref(false)

const isEditMode = computed(() => Boolean(route.params.id))
const title = computed(() => isEditMode.value ? 'Edit User' : 'Create User')
const roleOptions = [
  { value: '', label: '-' },
  { value: 'company_admin', label: 'Company admin' },
  { value: 'worker', label: 'Worker' },
]
const statusOptions = [
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
]

onMounted(async () => {
  loading.value = true

  await Promise.all([
    loadUser(),
    loadCompanies(),
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
  isAdmin.value = data.is_admin
  isBlocked.value = data.is_blocked

  if (data.company_user) {
    companyId.value = data.company_user.company_id || ''
    companyUserExternalId.value = data.company_user.external_id || ''
    companyUserRole.value = data.company_user.role || ''
    companyUserStatus.value = data.company_user.status || 'pending'
  }
}

async function loadCompanies() {
  const { data } = await http.get('/api/companies', {
    params: {
      sort: 'name',
    },
  })

  companyOptions.value = [
    { value: '', label: '-' },
    ...data.data.map((company) => ({
      value: company.id,
      label: company.name,
    })),
  ]
}

async function submit() {
  saving.value = true
  errors.value = {}

  const payload = {
    name: name.value,
    email: email.value,
    password: password.value || null,
    is_admin: isAdmin.value,
    is_blocked: isBlocked.value,
  }

  if (companyUserRole.value) {
    payload.company_user = {
      company_id: companyId.value || null,
      external_id: companyUserExternalId.value || null,
      role: companyUserRole.value,
      status: companyUserStatus.value,
    }
  }

  try {
    if (isEditMode.value) {
      await http.put(`/api/users/${route.params.id}`, payload)
      toast({ type: 'success', message: 'User updated successfully.' })
      router.push({ name: 'users' })
    } else {
      const response = await http.post('/api/users', payload)
      const { data } = await http.get(`/api/users/${response.data.id}`)

      toast({ type: 'success', message: 'User created successfully.' })
      router.push({ name: 'user-edit', params: { id: data.id } })
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

function cancel() {
  router.push({ name: 'users' })
}
</script>

<template>
  <form class="flex flex-col gap-4" @submit.prevent="submit">
    <div class="text-xl font-semibold text-slate-950">{{ title }}</div>

    <div class="grid gap-4 xl:grid-cols-2 xl:*:min-w-0">
      <app-card>
        <template #title>
          Account Details
        </template>

        <template #content>
          <div class="flex flex-col gap-4">
            <form-input
              v-model="name"
              label="Name"
              required
              size="lg"
              :error="errors.name"
            />

            <form-input
              v-model="email"
              label="Email"
              required
              size="lg"
              :error="errors.email"
            />

            <form-input
              v-model="password"
              label="Password"
              type="password"
              :required="!isEditMode"
              size="lg"
              :description="isEditMode ? 'Leave blank to keep the current password.' : 'The user will use this password to sign in.'"
              :error="errors.password"
            />

            <form-input
              v-model="isAdmin"
              label="Admin"
              secondary-label="User has administrator access"
              type="checkbox"
              description="Administrators can manage all companies, users, plans, and orders."
              :error="errors.is_admin"
            />

            <form-input
              v-model="isBlocked"
              label="Blocked"
              secondary-label="User is blocked"
              type="checkbox"
              description="Blocked users cannot sign in."
              :error="errors.is_blocked"
            />
          </div>
        </template>
      </app-card>

      <app-card>
        <template #title>
          Company Assignment
        </template>

        <template #content>
          <div class="flex flex-col gap-4">
            <form-select
              v-model="companyUserRole"
              label="Company role"
              size="lg"
              :options="roleOptions"
              :default-option="false"
              description="Controls what the user can access inside their assigned company."
              :error="errors['company_user.role']"
            />

            <form-select
              v-model="companyId"
              label="Company"
              size="lg"
              :options="companyOptions"
              :default-option="false"
              :error="errors['company_user.company_id']"
            />

            <form-input
              v-model="companyUserExternalId"
              label="Company external ID"
              size="lg"
              description="Optional identifier used to match this user with an external system."
              :error="errors['company_user.external_id']"
            />

            <form-select
              v-model="companyUserStatus"
              label="Company status"
              size="lg"
              :options="statusOptions"
              :default-option="false"
              description="Only approved company users can use company workflows."
              :error="errors['company_user.status']"
            />
          </div>
        </template>
      </app-card>
    </div>

    <div class="flex gap-2">
      <form-button
        type="submit"
        icon="save"
        label="Save"
        :loading="saving || loading"
      />
      <form-button
        severity="ternary"
        label="Cancel"
        @click.prevent="cancel"
      />
    </div>
  </form>
</template>
