<script setup>
import { ref } from 'vue'

const name = ref('')
const ownerEmail = ref('')
const filters = ref({})

const columns = [
  {
    field: 'name',
    header: 'Name',
    sortable: true,
  },
  {
    field: 'owner.email',
    header: 'Owner',
  },
  {
    field: 'subscription_plan.name',
    header: 'Subscription plan',
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
      owner_email: ownerEmail.value,
    },
  }
}

function onCancel() {
  name.value = ''
  ownerEmail.value = ''
  filters.value = {}
}
</script>

<template>
  <app-card>
    <template #title>
      Companies
    </template>

    <template #content>
      <filter-layout :on-search="onSearch" :on-cancel="onCancel" class="mb-6">
        <template #filters>
          <form-input v-model="name" label="Name" />
          <form-input v-model="ownerEmail" label="Owner email" />
        </template>
      </filter-layout>

      <app-grid
        :columns="columns"
        url="/api/companies"
        default-sort-field="created_at"
        default-sort-order="desc"
        :filters="filters"
      />
    </template>
  </app-card>
</template>
