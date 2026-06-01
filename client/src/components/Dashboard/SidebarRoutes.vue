<script setup>
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { authStore } from '../../stores/auth'

defineProps({
  isCollapsed: {
    type: Boolean,
    default: false,
  },
})

const route = useRoute()

const routes = [
  {
    name: 'dashboard',
    label: 'Dashboard',
    icon: 'pi pi-home',
  },
  {
    name: 'users',
    label: 'Users',
    icon: 'pi pi-users',
    roles: ['admin'],
  },
  {
    name: 'companies',
    label: 'Companies',
    icon: 'pi pi-building',
    roles: ['admin'],
  },
  {
    name: 'company-admins',
    label: 'Company Admins',
    icon: 'pi pi-user-plus',
    roles: ['admin'],
  },
  {
    name: 'subscription-plans',
    label: 'Subscription Plans',
    icon: 'pi pi-credit-card',
    roles: ['admin'],
  },
  {
    name: 'orders',
    label: 'Orders',
    icon: 'pi pi-shopping-cart',
    roles: ['admin'],
  },
  {
    name: 'company',
    label: 'Company',
    icon: 'pi pi-building',
    roles: ['company_admin'],
  },
  {
    name: 'company-users',
    label: 'Company Users',
    icon: 'pi pi-users',
    roles: ['company_admin'],
  },
  {
    name: 'workstreams',
    label: 'Workstreams',
    icon: 'pi pi-sitemap',
    roles: ['company_admin'],
  },
  {
    name: 'order-history',
    label: 'Order History',
    icon: 'pi pi-history',
    roles: ['company_admin'],
  },
  {
    name: 'planner',
    label: 'Planner',
    icon: 'pi pi-calendar',
    roles: ['company_admin'],
  },
  {
    name: 'timeoff-requests',
    label: 'Timeoff Requests',
    icon: 'pi pi-calendar-clock',
    roles: ['company_admin'],
  },
  {
    name: 'profile',
    label: 'Profile',
    icon: 'pi pi-user',
    roles: ['company_admin', 'team_lead', 'worker'],
  },
  {
    name: 'timesheet',
    label: 'Timesheet',
    icon: 'pi pi-clock',
    roles: ['company_admin', 'team_lead', 'worker'],
  },
  {
    name: 'work-log',
    label: 'Work Log',
    icon: 'pi pi-clipboard',
    roles: ['company_admin', 'team_lead', 'worker'],
  },
  {
    name: 'analytics',
    label: 'Analytics',
    icon: 'pi pi-chart-line',
    roles: ['admin'],
  },
]

const sidebarRoutes = computed(() => {
  return routes.filter((item) => !item.roles || item.roles.includes(authStore.userRole.value))
})
</script>

<template>
  <div class="mt-8 space-y-1">
    <RouterLink
      v-for="item in sidebarRoutes"
      :key="item.name"
      :to="{ name: item.name }"
      class="flex min-h-10 items-center rounded-lg text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
      :class="[
        isCollapsed ? 'justify-center px-2' : 'gap-3 px-3 py-2.5',
        route.name === item.name ? 'bg-brand-50 text-brand-900 ring-1 ring-inset ring-brand-100' : '',
      ]"
      :aria-label="item.label"
      :title="isCollapsed ? item.label : undefined"
    >
      <i :class="[item.icon, 'text-base']" />
      <span v-if="!isCollapsed">{{ item.label }}</span>
    </RouterLink>
  </div>
</template>
