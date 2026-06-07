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
  if (!isEditMode.value) {
    return
  }

  const { data } = await http.get(`/api/companies/${route.params.id}/owner-options`)

  ownerOptions.value = [
    { value: '', label: '-' },
    ...data.map((user) => ({
      value: user.id,
      label: `${user.name} (${user.email})`,
    })),
  ]
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
  ownerId.value = data.owner_id || ''
  subscriptionPlanId.value = data.subscription_plan_id || ''
}

async function submit() {
  saving.value = true
  errors.value = {}

  const payload = {
    name: name.value,
    subscription_plan_id: subscriptionPlanId.value || null,
  }

  try {
    if (isEditMode.value) {
      payload.owner_id = ownerId.value || null

      await http.put(`/api/companies/${route.params.id}`, payload)
      toast({ type: 'success', message: 'Company updated successfully.' })
      router.push({ name: 'companies' })
    } else {
      const response = await http.post('/api/companies', payload)
      const { data } = await http.get(`/api/companies/${response.data.id}`)

      toast({ type: 'success', message: 'Company created successfully.' })
      router.push({ name: 'company-edit', params: { id: data.id } })
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
          v-if="isEditMode"
          v-model="ownerId"
          label="Owner"
          size="lg"
          :options="ownerOptions"
          :default-option="false"
          description="The owner is the primary user responsible for this company."
          :error="errors.owner_id"
        />

        <form-select
          v-model="subscriptionPlanId"
          label="Subscription plan"
          size="lg"
          :options="subscriptionPlanOptions"
          :default-option="false"
          description="Determines which product features are available to the company."
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
