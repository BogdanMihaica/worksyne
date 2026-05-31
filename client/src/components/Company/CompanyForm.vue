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
const ownerId = ref('')
const subscriptionPlanId = ref('')
const ownerOptions = ref([])
const subscriptionPlanOptions = ref([])
const errors = ref({})
const loading = ref(false)
const saving = ref(false)
const currentOwner = ref(null)

const isEditMode = computed(() => Boolean(route.params.id))
const title = computed(() => isEditMode.value ? 'Edit Company' : 'Create Company')

onMounted(async () => {
  loading.value = true

  await loadCompany()

  await Promise.all([
    loadOwners(),
    loadSubscriptionPlans(),
  ])

  loading.value = false
})

async function loadOwners() {
  const { data } = await http.get('/api/users/without-company')

  ownerOptions.value = [
    { value: '', label: '-' },
    ...data.map((user) => ({
      value: user.id,
      label: `${user.name} (${user.email})`,
    })),
  ]

  if (currentOwner.value && !ownerOptions.value.some((option) => option.value === currentOwner.value.id)) {
    ownerOptions.value.push({
      value: currentOwner.value.id,
      label: `${currentOwner.value.name} (${currentOwner.value.email})`,
    })
  }
}

async function loadSubscriptionPlans() {
  const { data } = await http.get('/api/subscription-plans', {
    params: {
      sort: 'name',
    },
  })

  subscriptionPlanOptions.value = [
    { value: '', label: '-' },
    ...data.data.map((plan) => ({
      value: plan.id,
      label: plan.name,
    })),
  ]
}

async function loadCompany() {
  if (!isEditMode.value) {
    return
  }

  const { data } = await http.get(`/api/companies/${route.params.id}`)

  name.value = data.name
  ownerId.value = data.owner_id
  subscriptionPlanId.value = data.subscription_plan_id || ''

  const response = await http.get(`/api/users/${data.owner_id}`)

  currentOwner.value = response.data
}

async function submit() {
  saving.value = true
  errors.value = {}

  const payload = {
    name: name.value,
    owner_id: ownerId.value,
    subscription_plan_id: subscriptionPlanId.value || null,
  }

  try {
    if (isEditMode.value) {
      await http.put(`/api/companies/${route.params.id}`, payload)
    } else {
      await http.post('/api/companies', payload)
    }

    toast({ type: 'success', message: isEditMode.value ? 'Company updated successfully.' : 'Company created successfully.' })
    router.push({ name: 'companies' })
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
  router.push({ name: 'companies' })
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

        <form-select
          v-model="ownerId"
          label="Owner"
          required
          size="lg"
          :options="ownerOptions"
          :default-option="false"
          :error="errors.owner_id"
        />

        <form-select
          v-model="subscriptionPlanId"
          label="Subscription plan"
          size="lg"
          :options="subscriptionPlanOptions"
          :default-option="false"
          :error="errors.subscription_plan_id"
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
