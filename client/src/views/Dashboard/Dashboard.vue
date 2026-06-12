<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { authStore } from '../../stores/auth'
import { useHttp } from '../../plugins/http'
import { useAppToast } from '../../plugins/toast'

const http = useHttp()
const toast = useAppToast()

const dashboard = ref(null)
const loading = ref(false)

const isGlobalAdmin = computed(() => authStore.state.user?.role === 'admin')
const isCompanyAdmin = computed(() => authStore.state.user?.role === 'company_admin')
const flashcards = computed(() => dashboard.value?.flashcards || [])
const adminOverview = computed(() => dashboard.value?.admin_overview)
const attentionCards = computed(() => {
  const needsAttention = adminOverview.value?.needs_attention

  return [
    {
      label: 'Pending company admins',
      value: needsAttention?.pending_company_admins,
      icon: 'pi pi-user-plus',
      route: { name: 'company-admins' },
      classes: 'border-amber-200 bg-amber-50 text-amber-950',
    },
    {
      label: 'Blocked users',
      value: needsAttention?.blocked_users,
      icon: 'pi pi-user-minus',
      route: { name: 'users' },
      classes: 'border-red-200 bg-red-50 text-red-950',
    },
    {
      label: 'Pending orders',
      value: needsAttention?.pending_orders,
      icon: 'pi pi-clock',
      route: { name: 'orders' },
      classes: 'border-blue-200 bg-blue-50 text-blue-950',
    },
    {
      label: 'Failed orders',
      value: needsAttention?.failed_orders,
      icon: 'pi pi-exclamation-circle',
      route: { name: 'orders' },
      classes: 'border-red-200 bg-red-50 text-red-950',
    },
  ]
})

onMounted(() => {
  loadDashboard()
})

async function loadDashboard() {
  loading.value = true

  try {
    const { data } = await http.get('/api/dashboard')

    dashboard.value = data
  } catch {
    toast({ type: 'error', message: 'Unable to load dashboard.' })
  } finally {
    loading.value = false
  }
}

function formatNumber(value) {
  return Number(value || 0).toLocaleString()
}

function metricValue(value) {
  return loading.value ? '-' : formatNumber(value)
}

function formatDate(value) {
  if (!value) {
    return '-'
  }

  return new Date(value).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function formatMoney(amount, currency) {
  if (amount === null || amount === undefined) {
    return '-'
  }

  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: String(currency || 'USD').toUpperCase(),
  }).format(Number(amount))
}

function statusClasses(status) {
  const classes = {
    paid: 'bg-emerald-100 text-emerald-800',
    pending: 'bg-amber-100 text-amber-800',
    failed: 'bg-red-100 text-red-800',
  }

  return classes[status] || 'bg-slate-100 text-slate-700'
}

function flashcardClasses(severity) {
  const classes = {
    danger: 'border-red-200 bg-red-50 text-red-950',
    warning: 'border-amber-200 bg-amber-50 text-amber-950',
    success: 'border-emerald-200 bg-emerald-50 text-emerald-950',
  }

  return classes[severity] || 'border-slate-200 bg-slate-50 text-slate-950'
}

function flashcardIcon(severity) {
  const icons = {
    danger: 'pi pi-exclamation-triangle text-red-600',
    warning: 'pi pi-clock text-amber-600',
    success: 'pi pi-check-circle text-emerald-600',
  }

  return icons[severity] || 'pi pi-info-circle text-slate-600'
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-950">Dashboard</h1>
        <p class="text-sm text-slate-500">
          {{ isGlobalAdmin ? 'Platform operations at a glance' : 'Today at a glance' }}
        </p>
      </div>

      <form-button
        type="button"
        label="Refresh"
        icon="pi pi-refresh"
        severity="secondary"
        :disabled="loading"
        @click="loadDashboard"
      />
    </div>

    <template v-if="isGlobalAdmin">
      <app-card>
        <template #title>
          Needs attention
        </template>

        <template #content>
          <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <RouterLink
              v-for="card in attentionCards"
              :key="card.label"
              :to="card.route"
              class="rounded-lg border p-4 transition hover:-translate-y-0.5 hover:shadow-sm"
              :class="card.classes"
            >
              <div class="flex items-center justify-between gap-3">
                <div class="text-sm font-medium">{{ card.label }}</div>
                <i :class="card.icon" />
              </div>
              <div class="mt-3 text-3xl font-semibold tabular-nums">
                {{ metricValue(card.value) }}
              </div>
            </RouterLink>
          </div>
        </template>
      </app-card>

      <div class="grid gap-6 xl:grid-cols-2">
        <app-card>
          <template #title>
            Recent companies
          </template>

          <template #actions>
            <RouterLink
              :to="{ name: 'companies' }"
              class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
            >
              View all
            </RouterLink>
          </template>

          <template #content>
            <div v-if="loading" class="text-sm text-slate-500">Loading companies...</div>

            <div
              v-else-if="adminOverview?.recent_companies?.length"
              class="divide-y divide-slate-100"
            >
              <RouterLink
                v-for="company in adminOverview.recent_companies"
                :key="company.id"
                :to="{ name: 'company-edit', params: { id: company.id } }"
                class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"
              >
                <div class="min-w-0">
                  <div class="truncate font-medium text-slate-950">{{ company.name }}</div>
                  <div class="truncate text-sm text-slate-500">
                    {{ company.owner_name || company.owner_email || 'No owner' }}
                  </div>
                </div>
                <div class="shrink-0 text-right">
                  <div class="text-sm font-medium text-slate-700">{{ company.plan_name || 'No plan' }}</div>
                  <div class="text-xs text-slate-500">{{ formatDate(company.created_at) }}</div>
                </div>
              </RouterLink>
            </div>

            <div v-else class="text-sm text-slate-500">No companies yet.</div>
          </template>
        </app-card>

        <app-card>
          <template #title>
            Recent orders
          </template>

          <template #actions>
            <RouterLink
              :to="{ name: 'orders' }"
              class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
            >
              View all
            </RouterLink>
          </template>

          <template #content>
            <div v-if="loading" class="text-sm text-slate-500">Loading orders...</div>

            <div
              v-else-if="adminOverview?.recent_orders?.length"
              class="divide-y divide-slate-100"
            >
              <div
                v-for="order in adminOverview.recent_orders"
                :key="order.id"
                class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"
              >
                <div class="min-w-0">
                  <div class="truncate font-medium text-slate-950">
                    {{ order.company_name || 'Unknown company' }}
                  </div>
                  <div class="text-sm text-slate-500">
                    {{ order.plan_name || 'Unknown plan' }} · {{ formatDate(order.created_at) }}
                  </div>
                </div>
                <div class="shrink-0 text-right">
                  <div class="font-medium text-slate-950">
                    {{ formatMoney(order.amount, order.currency) }}
                  </div>
                  <span
                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                    :class="statusClasses(order.status)"
                  >
                    {{ order.status }}
                  </span>
                </div>
              </div>
            </div>

            <div v-else class="text-sm text-slate-500">No orders yet.</div>
          </template>
        </app-card>
      </div>
    </template>

    <div v-else class="grid gap-4 md:grid-cols-2">
      <app-card>
        <template #title>
          Total worked items today
        </template>

        <template #content>
          <div class="text-3xl font-semibold text-slate-950">
            {{ metricValue(dashboard?.total_worked_items_today) }}
          </div>
          <div class="mt-1 text-sm text-slate-500">
            {{ isCompanyAdmin ? 'Units logged by the company today' : 'Units logged by you today' }}
          </div>
        </template>
      </app-card>

      <app-card>
        <template #title>
          Unread notifications
        </template>

        <template #content>
          <div class="text-3xl font-semibold text-slate-950">
            {{ metricValue(dashboard?.unread_notifications_count) }}
          </div>
          <div class="mt-1 text-sm text-slate-500">Notifications waiting for you</div>
        </template>
      </app-card>
    </div>

    <app-card v-if="isCompanyAdmin">
      <template #title>
        Forecast signals
      </template>

      <template #content>
        <div v-if="loading" class="text-sm text-slate-500">Loading forecast signals...</div>

        <div v-else-if="flashcards.length" class="grid gap-4 lg:grid-cols-3">
          <div
            v-for="card in flashcards"
            :key="card.type"
            class="rounded-lg border p-4 shadow-sm"
            :class="flashcardClasses(card.severity)"
          >
            <div class="flex items-start gap-3">
              <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white/70">
                <i :class="flashcardIcon(card.severity)" />
              </div>
              <div class="min-w-0">
                <div class="text-sm font-semibold">{{ card.title }}</div>
                <div class="mt-1 text-sm leading-6 opacity-85">{{ card.message }}</div>
                <div class="mt-3 text-2xl font-semibold tabular-nums">{{ formatNumber(card.metric) }}</div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
          No forecast alerts right now.
        </div>
      </template>
    </app-card>
  </div>
</template>
