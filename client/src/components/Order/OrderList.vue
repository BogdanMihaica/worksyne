<script setup>
import { onMounted, ref } from 'vue'
import { useHttp } from '../../plugins/http'

const http = useHttp()
const company = ref('')
const userEmail = ref('')
const subscriptionPlan = ref('')
const status = ref('')
const filters = ref({})
const subscriptionPlanOptions = ref([])
const statusOptions = [
  { value: '', label: '-' },
  { value: 'pending', label: 'Pending' },
  { value: 'paid', label: 'Paid' },
  { value: 'failed', label: 'Failed' },
]

const columns = [
  {
    field: 'company.name',
    header: 'Company',
  },
  {
    field: 'user.email',
    header: 'User email',
  },
  {
    field: 'subscription_plan.name',
    header: 'Subscription plan',
  },
  {
    field: 'amount',
    header: 'Amount',
    sortable: true,
    numeric: true,
  },
  {
    field: 'currency',
    header: 'Currency',
  },
  {
    field: 'status',
    header: 'Status',
    sortable: true,
  },
  {
    field: 'external_id',
    header: 'External ID',
  },
  {
    field: 'created_at',
    header: 'Created at',
    sortable: true,
    date: true,
  },
]

onMounted(() => {
  loadSubscriptionPlans()
})

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
      company: company.value,
      user_email: userEmail.value,
      subscription_plan_id: subscriptionPlan.value,
      status: status.value,
    },
  }
}

function onCancel() {
  company.value = ''
  userEmail.value = ''
  subscriptionPlan.value = ''
  status.value = ''
  filters.value = {}
}
</script>

<template>
  <app-card>
    <template #title>
      Orders
    </template>

    <template #content>
      <filter-layout :on-search="onSearch" :on-cancel="onCancel" class="mb-6">
        <template #filters>
          <form-input v-model="company" label="Company" />
          <form-input v-model="userEmail" label="User email" />
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
        :columns="columns"
        url="/api/orders"
        default-sort-field="created_at"
        default-sort-order="desc"
        :filters="filters"
      />
    </template>
  </app-card>
</template>
