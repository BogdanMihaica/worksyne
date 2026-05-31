<script setup>
import Button from 'primevue/button'
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useHttp } from '../../plugins/http'

const http = useHttp()
const plans = ref([])
const loading = ref(false)
const contactEmail = 'hello@worksyne.local.test'
const contactHref = `mailto:${contactEmail}?subject=Worksyne%20pricing%20inquiry`

onMounted(() => {
  loadPlans()
})

async function loadPlans() {
  loading.value = true

  const { data } = await http.get('/api/pricing')

  plans.value = data
  loading.value = false
}

function formatPrice(price) {
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
  }).format(Number(price))
}
</script>

<template>
  <div class="min-h-screen bg-[#f7f8fb] text-slate-950">
    <div class="border-b border-slate-200 bg-white/95">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 lg:px-8">
        <RouterLink :to="{ name: 'landing' }" class="flex items-center gap-3">
          <span class="grid h-10 w-10 place-items-center rounded-md bg-brand-900 text-white">
            <i class="pi pi-briefcase text-lg" />
          </span>
          <span class="text-base font-semibold tracking-[0.08em] text-brand-900">WORKSYNE</span>
        </RouterLink>

        <div class="flex items-center gap-2">
          <RouterLink :to="{ name: 'landing' }">
            <Button label="Platform" text class="text-slate-700!" />
          </RouterLink>
          <RouterLink :to="{ name: 'sign-in' }">
            <Button label="Sign in" text class="text-slate-700!" />
          </RouterLink>
        </div>
      </div>
    </div>

    <div class="px-5 py-14 lg:px-8">
      <div class="mx-auto max-w-7xl">
        <div class="max-w-3xl">
          <p class="text-sm font-semibold uppercase text-brand-700">Pricing</p>
          <h1 class="mt-4 text-4xl font-semibold leading-tight text-slate-950 sm:text-5xl">
            Choose the operating plan that fits your teams.
          </h1>
          <p class="mt-5 text-lg leading-8 text-slate-600">
            Start with the essentials, expand planning and analytics when your company needs deeper operational control.
          </p>
        </div>

        <div v-if="loading" class="mt-10 rounded-md border border-slate-200 bg-white p-6 text-slate-500">
          Loading pricing...
        </div>

        <div v-else class="mt-10 grid gap-5 lg:grid-cols-3">
          <div
            v-for="plan in plans"
            :key="plan.id"
            class="flex flex-col rounded-md border border-slate-200 bg-white p-6 shadow-sm"
          >
            <div class="border-b border-slate-200 pb-5">
              <h2 class="text-2xl font-semibold text-slate-950">{{ plan.name }}</h2>
              <div class="mt-4 flex items-end gap-2">
                <span class="text-4xl font-semibold text-brand-900">{{ formatPrice(plan.price) }}</span>
                <span class="pb-1 text-sm text-slate-500">/ month</span>
              </div>
            </div>

            <div class="flex flex-1 flex-col justify-between pt-5">
              <div class="grid gap-3">
                <div
                  v-for="feature in plan.features"
                  :key="feature.id"
                  class="flex gap-3"
                >
                  <span class="mt-1 text-emerald-600">
                    <i class="pi pi-check" />
                  </span>
                  <div>
                    <p class="font-medium text-slate-900">{{ feature.name }}</p>
                    <p v-if="feature.description" class="mt-1 text-sm leading-6 text-slate-500">
                      {{ feature.description }}
                    </p>
                  </div>
                </div>
              </div>

              <a :href="contactHref" class="mt-8">
                <Button label="Register your company" icon="pi pi-building" class="w-full bg-brand-900! text-white!" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
