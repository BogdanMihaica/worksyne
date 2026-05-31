<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useHttp } from '../../plugins/http'
import { authStore } from '../../stores/auth'

const http = useHttp()
const subscriptionPlan = ref('')
const status = ref('')
const subscriptionPlanOptions = ref([])
const companyId = computed(() => authStore.state.user?.company_user?.company_id)
const filters = ref({})
const statusOptions = [
  { value: '', label: '-' },
  { value: 'pending', label: 'Pending' },
  { value: 'paid', label: 'Paid' },
  { value: 'failed', label: 'Failed' },
]

const columns = [
  { field: 'user.email', header: 'User email' },
  { field: 'subscription_plan.name', header: 'Subscription plan' },
  { field: 'amount', header: 'Amount', sortable: true, numeric: true },
  { field: 'currency', header: 'Currency' },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'external_id', header: 'External ID' },
  { field: 'created_at', header: 'Created at', sortable: true, date: true },
]

onMounted(() => {
  loadSubscriptionPlans()
})

function baseFilters() {
  return {
    filter: {
      company_id: companyId.value,
    },
  }
}

watch(companyId, () => {
  filters.value = baseFilters()
}, { immediate: true })

async function loadSubscriptionPlans() {
  const { data } = await http.get('/api/subscription-plans')

  subscriptionPlanOptions.value = [
    { value: '', label: '-' },
    ...data.data.map((plan) => ({
      value: plan.id,
      label: plan.name,
    })),
  ]
}

function onSearch() {
  filters.value = {
    filter: {
      company_id: companyId.value,
      subscription_plan_id: subscriptionPlan.value,
      status: status.value,
    },
  }
}

function onCancel() {
  subscriptionPlan.value = ''
  status.value = ''
  filters.value = baseFilters()
}
</script>

<template>
  <app-card>
    <template #title>
      Order History
    </template>

    <template #content>
      <filter-layout :on-search="onSearch" :on-cancel="onCancel" class="mb-6">
        <template #filters>
          <form-select
            v-model="subscriptionPlan"
            label="Subscription plan"
            :options="subscriptionPlanOptions"
            :default-option="false"
          />
          <form-select
            v-model="status"
            label="Status"
            :options="statusOptions"
            :default-option="false"
          />
        </template>
      </filter-layout>

      <app-grid
        v-if="companyId"
        :columns="columns"
        url="/api/orders"
        default-sort-field="created_at"
        default-sort-order="desc"
        :filters="filters"
      />
    </template>
  </app-card>
</template>
