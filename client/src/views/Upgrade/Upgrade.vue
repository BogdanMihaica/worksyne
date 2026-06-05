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

const paidPlans = computed(() => plans.value.filter((plan) => Number(plan.price) > 0))
const currentPlanName = computed(() => authStore.state.user?.company_user?.company?.subscription_plan?.name || '')

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

function formatPrice(price) {
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
  }).format(Number(price))
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

    <div v-else class="grid gap-5 lg:grid-cols-2">
      <app-card v-for="plan in paidPlans" :key="plan.id">
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
                v-for="feature in plan.features"
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
              type="button"
              icon="pi pi-crown"
              :label="currentPlanName === plan.name ? 'Current plan' : `Buy ${plan.name}`"
              :disabled="currentPlanName === plan.name"
              :loading="checkoutPlanId === plan.id"
              class="w-full bg-brand-900! text-white!"
              @click="startCheckout(plan)"
            />
          </div>
        </template>
      </app-card>
    </div>
  </div>
</template>
