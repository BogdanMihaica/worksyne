<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { authStore } from '../../stores/auth'

const router = useRouter()
const name = ref('')
const email = ref('')
const externalId = ref('')
const status = ref('')

const companyId = computed(() => authStore.state.user?.company_user?.company_id)
const filters = ref({})
const columns = [
  { field: 'user.name', header: 'Name' },
  { field: 'user.email', header: 'Email' },
  { field: 'external_id', header: 'External ID', sortable: true },
  { field: 'role', header: 'Role', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'created_at', header: 'Created at', date: true, sortable: true },
  {
    field: 'actions',
    header: 'Actions',
    type: 'actions',
    widthFit: true,
    items: ({ data }) => data.user
      ? [
          {
            label: 'Edit',
            icon: 'pen-to-square',
            severity: 'secondary',
            onClick: () => router.push({ name: 'company-user-edit', params: { id: data.user.id } }),
          },
        ]
      : [],
  },
]

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

function onSearch() {
  filters.value = {
    filter: {
      company_id: companyId.value,
      name: name.value,
      email: email.value,
      external_id: externalId.value,
      status: status.value,
    },
  }
}

function onCancel() {
  name.value = ''
  email.value = ''
  externalId.value = ''
  status.value = ''
  filters.value = baseFilters()
}
</script>

<template>
  <app-card>
    <template #title>
      Company Users
    </template>

    <template #content>
      <filter-layout :on-search="onSearch" :on-cancel="onCancel" class="mb-6">
        <template #filters>
          <form-input v-model="name" label="Name" />
          <form-input v-model="email" label="Email" />
          <form-input v-model="externalId" label="External ID" />
          <form-input v-model="status" label="Status" />
        </template>
      </filter-layout>

      <app-grid
        v-if="companyId"
        :columns="columns"
        url="/api/company-users"
        default-sort-field="created_at"
        default-sort-order="desc"
        :filters="filters"
      />
    </template>

    <template #actions>
      <form-button
        label="New"
        icon="plus"
        @click="router.push({ name: 'company-user-create' })"
      />
    </template>
  </app-card>
</template>
