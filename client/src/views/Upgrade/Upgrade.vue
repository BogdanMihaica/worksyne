<script setup>
import Button from 'primevue/button'
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { authStore } from '../../stores/auth'
import { useHttp } from '../../plugins/http'
import { useAppToast } from '../../plugins/toast'

const route = useRoute()
const http = useHttp()
const toast = useAppToast()

const plans = ref([])
const loading = ref(false)
const confirming = ref(false)
const checkoutPlanId = ref(null)
const downgrading = ref(false)
const showDowngradeConfirmation = ref(false)

const currentPlanName = computed(() => authStore.state.user?.company_user?.company?.subscription_plan?.name || '')
const freePlanFeatures = [
  {
    id: 'free-company',
    name: 'Company management',
    description: 'Manage your company profile and core workspace settings.',
  },
  {
    id: 'free-users',
    name: 'Team management',
    description: 'Add and manage company users, roles, and approval status.',
  },
  {
    id: 'free-workstreams',
    name: 'Workstream management',
    description: 'Create workstreams and organize users across your operation.',
  },
]

onMounted(async () => {
  await loadPlans()

  if (route.query.checkout === 'success' && route.query.session_id) {
    confirmCheckout(route.query.session_id)
  } else if (route.query.checkout === 'cancelled') {
    toast({ type: 'warning', message: 'Stripe checkout was cancelled.' })
  }
})

async function loadPlans() {
  loading.value = true

  try {
    const { data } = await http.get('/api/pricing')

    plans.value = data
  } catch {
    toast({ type: 'error', message: 'Unable to load subscription plans.' })
  } finally {
    loading.value = false
  }
}

async function startCheckout(plan) {
  checkoutPlanId.value = plan.id

  try {
    const { data } = await http.post('/api/company-subscription/checkout', {
      subscription_plan_id: plan.id,
    })

    window.location.href = data.checkout_url
  } catch (error) {
    toast({ type: 'error', message: error.response?.data?.message || 'Unable to start Stripe checkout.' })
  } finally {
    checkoutPlanId.value = null
  }
}

async function confirmCheckout(sessionId) {
  confirming.value = true

  try {
    await http.get('/api/company-subscription/checkout/confirm', {
      params: {
        session_id: sessionId,
      },
    })
    await authStore.fetchUser()
    toast({ type: 'success', message: 'Subscription upgraded.' })
  } catch (error) {
    toast({ type: 'error', message: error.response?.data?.message || 'Unable to confirm Stripe checkout.' })
  } finally {
    confirming.value = false
  }
}

async function downgradeToFree() {
  downgrading.value = true

  try {
    const { data } = await http.post('/api/company-subscription/downgrade')

    await authStore.fetchUser()
    showDowngradeConfirmation.value = false
    toast({ type: 'success', message: data.message })
  } catch (error) {
    toast({ type: 'error', message: error.response?.data?.message || 'Unable to switch to the Free plan.' })
  } finally {
    downgrading.value = false
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
  }).format(Number(price))
}

function planFeatures(plan) {
  if (Number(plan.price) === 0) {
    return freePlanFeatures
  }

  return [
    {
      id: `${plan.id}-free-plan`,
      name: 'Everything from the Free Plan',
      description: 'Includes company, team, and workstream management.',
    },
    ...plan.features,
  ]
}
</script>

<template>
  <div class="grid gap-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-950">Upgrade</h1>
        <p class="mt-1 text-sm text-slate-500">Choose the plan that unlocks the workflows your company needs.</p>
      </div>
      <div v-if="currentPlanName" class="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600">
        Current plan: <span class="font-semibold text-slate-950">{{ currentPlanName }}</span>
      </div>
    </div>

    <div v-if="loading || confirming" class="rounded-md border border-slate-200 bg-white p-6 text-sm text-slate-500">
      {{ confirming ? 'Confirming checkout...' : 'Loading plans...' }}
    </div>

    <div v-else class="grid gap-5 lg:grid-cols-3">
      <app-card v-for="plan in plans" :key="plan.id">
        <template #title>
          {{ plan.name }}
        </template>

        <template #content>
          <div class="flex flex-col gap-5">
            <div>
              <span class="text-3xl font-semibold text-brand-900">{{ formatPrice(plan.price) }}</span>
              <span class="ml-1 text-sm text-slate-500">/ month per company</span>
            </div>

            <div class="grid gap-3">
              <div
                v-for="feature in planFeatures(plan)"
                :key="feature.id"
                class="flex gap-3 rounded-md bg-slate-50 p-3"
              >
                <i class="pi pi-check mt-1 text-emerald-600" />
                <div>
                  <div class="text-sm font-semibold text-slate-950">{{ feature.name }}</div>
                  <div class="mt-1 text-sm leading-6 text-slate-500">{{ feature.description }}</div>
                </div>
              </div>
            </div>

            <Button
              v-if="Number(plan.price) > 0"
              type="button"
              icon="pi pi-crown"
              :label="currentPlanName === plan.name ? 'Current plan' : `Buy ${plan.name}`"
              :disabled="currentPlanName === plan.name"
              :loading="checkoutPlanId === plan.id"
              class="w-full bg-brand-900! text-white!"
              @click="startCheckout(plan)"
            />
            <Button
              v-else
              type="button"
              icon="pi pi-arrow-down"
              :label="currentPlanName === plan.name ? 'Current plan' : 'Switch to Free'"
              :disabled="currentPlanName === plan.name"
              :loading="downgrading"
              severity="secondary"
              outlined
              class="w-full"
              @click="showDowngradeConfirmation = true"
            />
          </div>
        </template>
      </app-card>
    </div>

    <confirm-dialog
      v-model="showDowngradeConfirmation"
      title="Switch to Free plan?"
      message="Your paid subscription will be canceled immediately and paid features will no longer be available."
      confirm-label="Switch to Free"
      confirm-severity="danger"
      :loading="downgrading"
      @confirm="downgradeToFree"
    />
  </div>
</template>
