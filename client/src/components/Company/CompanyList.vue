<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
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
  {
    field: 'actions',
    header: 'Actions',
    type: 'actions',
    widthFit: true,
    items: ({ data }) => [
      {
        label: 'Edit',
        icon: 'pen-to-square',
        severity: 'secondary',
        onClick: () => openEditCompany(data.id),
      },
    ],
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

function openCreateCompany() {
  router.push({ name: 'company-create' })
}

function openEditCompany(id) {
  router.push({ name: 'company-edit', params: { id } })
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

    <template #actions>
      <form-button
        label="New"
        icon="plus"
        @click="openCreateCompany"
      />
    </template>
  </app-card>
</template>
