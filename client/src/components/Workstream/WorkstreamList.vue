<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useStrings } from '../../plugins/strings'
import { authStore } from '../../stores/auth'

const router = useRouter()
const strings = useStrings()
const name = ref('')
const companyId = computed(() => authStore.state.user?.company_user?.company_id)
const filters = ref({})
const columns = [
  { field: 'name', header: 'Name', sortable: true, format: strings.upperCase },
  { field: 'created_at', header: 'Created at', sortable: true, date: true },
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
        onClick: () => router.push({ name: 'workstream-edit', params: { id: data.id } }),
      },
    ],
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
    },
  }
}

function onCancel() {
  name.value = ''
  filters.value = baseFilters()
}
</script>

<template>
  <app-card>
    <template #title>
      Workstreams
    </template>

    <template #content>
      <filter-layout :on-search="onSearch" :on-cancel="onCancel" class="mb-6">
        <template #filters>
          <form-input v-model="name" label="Name" />
        </template>
      </filter-layout>

      <app-grid
        v-if="companyId"
        :columns="columns"
        url="/api/workstreams"
        default-sort-field="created_at"
        default-sort-order="desc"
        :filters="filters"
      />
    </template>

    <template #actions>
      <form-button label="New" icon="plus" @click="router.push({ name: 'workstream-create' })" />
    </template>
  </app-card>
</template>
