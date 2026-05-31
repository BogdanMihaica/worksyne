<script setup>
import { computed } from 'vue'
import { authStore } from '../../stores/auth'

const user = computed(() => authStore.state.user || {})
const companyUser = computed(() => user.value.company_user || {})
const company = computed(() => companyUser.value.company || {})

const details = computed(() => [
  { label: 'Name', value: user.value.name },
  { label: 'Email', value: user.value.email },
  { label: 'Role', value: formatValue(user.value.role) },
  { label: 'Status', value: formatValue(companyUser.value.status) },
  { label: 'External ID', value: companyUser.value.external_id },
  { label: 'Company', value: company.value.name },
])

function formatValue(value) {
  return value ? String(value).replaceAll('_', ' ') : null
}
</script>

<template>
  <div class="grid gap-5 xl:grid-cols-[1fr_22rem]">
    <app-card size="full">
      <template #title>
        User Profile
      </template>

      <template #content>
        <div class="grid gap-4 md:grid-cols-2">
          <div
            v-for="item in details"
            :key="item.label"
            class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3"
          >
            <div class="text-xs font-semibold uppercase text-slate-400">{{ item.label }}</div>
            <div class="mt-1 text-sm font-semibold capitalize text-slate-900">
              {{ item.value || '-' }}
            </div>
          </div>
        </div>
      </template>
    </app-card>

    <app-card size="full">
      <template #title>
        Account
      </template>

      <template #content>
        <div class="flex items-center gap-3">
          <div class="grid h-12 w-12 place-items-center rounded-lg bg-brand-900 text-sm font-bold text-white">
            {{ user.name?.slice(0, 2).toUpperCase() }}
          </div>
          <div class="min-w-0">
            <div class="truncate text-sm font-semibold text-slate-950">{{ user.name }}</div>
            <div class="truncate text-sm text-slate-500">{{ user.email }}</div>
          </div>
        </div>
      </template>
    </app-card>
  </div>
</template>
