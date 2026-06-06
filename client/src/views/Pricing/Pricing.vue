<script setup>
import Button from 'primevue/button'
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useHttp } from '../../plugins/http'

const http = useHttp()
const plans = ref([])
const loading = ref(false)

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
    minimumFractionDigits: Number(price) % 1 === 0 ? 0 : 2,
    maximumFractionDigits: 2,
  }).format(Number(price))
}
</script>

<template>
  <div class="min-h-screen bg-[#f7f8fb] text-slate-950">
    <public-navigation />

    <div class="px-5 py-14 lg:px-8">
      <div class="mx-auto max-w-7xl">
        <div class="max-w-3xl">
          <p class="text-sm font-semibold uppercase text-brand-700">Pricing</p>
          <h1 class="mt-4 text-4xl font-semibold leading-tight text-slate-950 sm:text-5xl">
            Choose the Worksyne plan that fits your company.
          </h1>
          <p class="mt-5 text-lg leading-8 text-slate-600">
            Start free, unlock operational workflows with Pro, and add forecasting intelligence with Enterprise.
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
                <span class="pb-1 text-sm text-slate-500">/ month per company</span>
              </div>
            </div>

            <div class="flex flex-1 flex-col justify-between pt-5">
              <div v-if="plan.features.length" class="grid gap-3">
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
              <div v-else class="rounded-md bg-slate-50 p-4 text-sm leading-6 text-slate-500">
                Includes the free company management features. Upgrade when you need time tracking, notifications, capacity models, or forecasting.
              </div>

              <RouterLink :to="{ name: 'sign-in' }" class="mt-8">
                <Button
                  :label="Number(plan.price) === 0 ? 'Start free' : 'Choose in app'"
                  :icon="Number(plan.price) === 0 ? 'pi pi-building' : 'pi pi-crown'"
                  class="w-full bg-brand-900! text-white!"
                />
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
