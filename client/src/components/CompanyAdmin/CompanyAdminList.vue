<script setup>
import { ref } from 'vue'

const name = ref('')
const email = ref('')
const company = ref('')
const status = ref('')
const filters = ref({
  filter: {
    role: 'company_admin',
  },
})

const columns = [
  {
    field: 'user.name',
    header: 'Name',
  },
  {
    field: 'user.email',
    header: 'Email',
  },
  {
    field: 'company.name',
    header: 'Company',
  },
  {
    field: 'status',
    header: 'Status',
  },
  {
    field: 'created_at',
    header: 'Created at',
    date: true,
  },
]

function onSearch() {
  filters.value = {
    filter: {
      role: 'company_admin',
      name: name.value,
      email: email.value,
      company: company.value,
      status: status.value,
    },
  }
}

function onCancel() {
  name.value = ''
  email.value = ''
  company.value = ''
  status.value = ''
  filters.value = {
    filter: {
      role: 'company_admin',
    },
  }
}
</script>

<template>
  <app-card>
    <template #title>
      Company Admins
    </template>

    <template #content>
      <filter-layout :on-search="onSearch" :on-cancel="onCancel" class="mb-6">
        <template #filters>
          <form-input v-model="name" label="Name" />
          <form-input v-model="email" label="Email" />
          <form-input v-model="company" label="Company" />
          <form-input v-model="status" label="Status" />
        </template>
      </filter-layout>

      <app-grid
        :columns="columns"
        url="/api/company-users"
        default-sort-field="created_at"
        default-sort-order="desc"
        :filters="filters"
      />
    </template>
  </app-card>
</template>
