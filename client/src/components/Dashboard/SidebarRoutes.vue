<script setup>
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { authStore } from '../../stores/auth'

defineProps({
  isCollapsed: {
    type: Boolean,
    default: false,
  },
})

const route = useRoute()
const storageKey = 'worksyne_sidebar_categories'

const routeGroups = [
  {
    key: 'main',
    label: 'Main',
    icon: 'pi pi-home',
    routes: [
      {
        name: 'dashboard',
        label: 'Dashboard',
        icon: 'pi pi-home',
      },
    ],
  },
  {
    key: 'platform',
    label: 'Platform',
    icon: 'pi pi-shield',
    routes: [
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
        name: 'analytics',
        label: 'Analytics',
        icon: 'pi pi-chart-line',
        roles: ['admin'],
      },
    ],
  },
  {
    key: 'company',
    label: 'Company',
    icon: 'pi pi-building',
    routes: [
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
        name: 'capacity-models',
        label: 'Capacity Models',
        icon: 'pi pi-gauge',
        roles: ['company_admin'],
        feature: 'capacity-models',
      },
      {
        name: 'forecast',
        label: 'Forecast',
        icon: 'pi pi-chart-bar',
        roles: ['company_admin'],
        feature: 'forecast',
      },
      {
        name: 'company-notifications',
        label: 'Notifications',
        icon: 'pi pi-bell',
        roles: ['company_admin'],
        feature: 'notifications',
      },
      {
        name: 'order-history',
        label: 'Order History',
        icon: 'pi pi-history',
        roles: ['company_admin'],
      },
    ],
  },
  {
    key: 'operations',
    label: 'Operations',
    icon: 'pi pi-calendar',
    routes: [
      {
        name: 'timeoff-requests',
        label: 'Timeoff Requests',
        icon: 'pi pi-calendar-clock',
        roles: ['company_admin'],
        feature: 'company-timeoff',
      },
      {
        name: 'company-work-logs',
        label: 'Work Logs',
        icon: 'pi pi-list-check',
        roles: ['company_admin'],
        feature: 'time-logging',
      },
      {
        name: 'timesheet',
        label: 'Timesheet',
        icon: 'pi pi-clock',
        roles: ['company_admin', 'worker'],
        feature: 'company-timeoff',
      },
      {
        name: 'work-log',
        label: 'Log Work',
        icon: 'pi pi-clipboard',
        roles: ['company_admin', 'worker'],
        feature: 'time-logging',
      },
    ],
  },
  {
    key: 'personal',
    label: 'Personal',
    icon: 'pi pi-user',
    routes: [
      {
        name: 'profile',
        label: 'Profile',
        icon: 'pi pi-user',
        roles: ['company_admin', 'worker'],
      },
    ],
  },
]

const expandedGroups = ref({
  main: true,
  platform: true,
  company: true,
  operations: true,
  personal: true,
  ...storedExpandedGroups(),
})

const sidebarGroups = computed(() => {
  return routeGroups
    .map((group) => ({
      ...group,
      routes: group.routes.filter((item) => {
        const hasRole = !item.roles || item.roles.includes(authStore.userRole.value)
        const hasFeature = !item.feature || authStore.hasFeature(item.feature)

        return hasRole && hasFeature
      }),
    }))
    .filter((group) => group.routes.length > 0)
})

watch(
  expandedGroups,
  (value) => {
    localStorage.setItem(storageKey, JSON.stringify(value))
  },
  { deep: true },
)

function storedExpandedGroups() {
  try {
    return JSON.parse(localStorage.getItem(storageKey) || '{}')
  } catch {
    return {}
  }
}

function toggleGroup(key) {
  expandedGroups.value[key] = !expandedGroups.value[key]
}

function isGroupActive(group) {
  return group.routes.some((item) => item.name === route.name)
}
</script>

<template>
  <div class="mt-8 min-h-0 flex-1 space-y-3 overflow-y-auto overscroll-contain pb-4">
    <div v-for="group in sidebarGroups" :key="group.key" class="space-y-1">
      <button
        type="button"
        class="flex min-h-9 w-full items-center rounded-lg text-xs font-semibold uppercase text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
        :class="[
          isCollapsed ? 'justify-center px-2' : 'gap-2 px-3',
          isGroupActive(group) ? 'text-brand-900' : '',
        ]"
        :aria-expanded="expandedGroups[group.key]"
        :aria-label="`${expandedGroups[group.key] ? 'Collapse' : 'Expand'} ${group.label}`"
        :title="isCollapsed ? group.label : undefined"
        @click="toggleGroup(group.key)"
      >
        <i :class="[group.icon, 'text-sm']" />
        <span v-if="!isCollapsed" class="flex-1 text-left">{{ group.label }}</span>
        <i
          v-if="!isCollapsed"
          class="pi text-[10px] transition-transform"
          :class="expandedGroups[group.key] ? 'pi-chevron-down' : 'pi-chevron-right'"
        />
      </button>

      <div v-if="expandedGroups[group.key]" class="space-y-1">
        <RouterLink
          v-for="item in group.routes"
          :key="item.name"
          :to="{ name: item.name }"
          class="flex min-h-10 items-center rounded-lg text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
          :class="[
            isCollapsed ? 'justify-center px-2' : 'gap-3 py-2.5 pl-8 pr-3',
            route.name === item.name ? 'bg-brand-50 text-brand-900 ring-1 ring-inset ring-brand-100' : '',
          ]"
          :aria-label="item.label"
          :title="isCollapsed ? item.label : undefined"
        >
          <i :class="[item.icon, 'text-base']" />
          <span v-if="!isCollapsed">{{ item.label }}</span>
        </RouterLink>
      </div>
    </div>
  </div>
</template>
