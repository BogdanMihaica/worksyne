<script setup>
import { ref } from 'vue'

const name = ref('')
const email = ref('')
const filters = ref({})

const columns = [
  {
    field: 'name',
    header: 'Name',
    sortable: true,
  },
  {
    field: 'email',
    header: 'Email',
    sortable: true,
  },
  {
    field: 'is_admin',
    header: 'Admin',
    boolean: true,
    widthFit: true,
  },
  {
    field: 'is_email_verified',
    header: 'Email verified',
    boolean: true,
    widthFit: true,
  },
  {
    field: 'is_blocked',
    header: 'Blocked',
    boolean: true,
    widthFit: true,
  },
  {
    field: 'created_at',
    header: 'Created at',
    sortable: true,
    date: true,
  },
]

function onSearch() {
  filters.value = {
    filter: {
      name: name.value,
      email: email.value,
    },
  }
}

function onCancel() {
  name.value = ''
  email.value = ''
  filters.value = {}
}
</script>

<template>
  <app-card>
    <template #title>
      Users
    </template>

    <template #content>
      <filter-layout :on-search="onSearch" :on-cancel="onCancel" class="mb-6">
        <template #filters>
          <form-input v-model="name" label="Name" />
          <form-input v-model="email" label="Email" />
        </template>
      </filter-layout>

      <app-grid
        :columns="columns"
        url="/api/users"
        default-sort-field="created_at"
        default-sort-order="desc"
        :filters="filters"
      />
    </template>
  </app-card>
</template>
