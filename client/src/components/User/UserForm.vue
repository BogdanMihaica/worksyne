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
  <app-card size="medium">
    <template #title>
      {{ title }}
    </template>

    <template #content>
      <form class="flex flex-col gap-4" @submit.prevent="submit">
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
          :error="errors.password"
        />

        <form-input
          v-model="isAdmin"
          label="Admin"
          secondary-label="User has administrator access"
          type="checkbox"
          :error="errors.is_admin"
        />

        <form-input
          v-model="isBlocked"
          label="Blocked"
          secondary-label="User is blocked"
          type="checkbox"
          :error="errors.is_blocked"
        />

        <form-select
          v-model="companyUserRole"
          label="Company role"
          size="lg"
          :options="roleOptions"
          :default-option="false"
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
          :error="errors['company_user.external_id']"
        />

        <form-select
          v-model="companyUserStatus"
          label="Company status"
          size="lg"
          :options="statusOptions"
          :default-option="false"
          :error="errors['company_user.status']"
        />

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
  </app-card>
</template>
