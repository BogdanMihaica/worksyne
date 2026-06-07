<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useHttp } from '../../plugins/http'

const http = useHttp()
const plans = ref([])
const loading = ref(false)
const coreFeatures = [
  {
    key: 'company-management',
    name: 'Company management',
    description: 'Manage the company profile, plan, and core workspace settings.',
  },
  {
    key: 'team-management',
    name: 'Team management',
    description: 'Add and manage company users, roles, approval status, and seniority.',
  },
  {
    key: 'workstream-management',
    name: 'Workstream management',
    description: 'Create workstreams and organize company users across operational areas.',
  },
  {
    key: 'manual-work-logging',
    name: 'Manual work logging',
    description: 'Record completed units, work days, references, and notes.',
  },
]

const planFeatures = computed(() => {
  const features = new Map()

  plans.value.forEach((plan) => {
    plan.features.forEach((feature) => {
      features.set(feature.key, feature)
    })
  })

  return [...features.values()].sort((first, second) => first.name.localeCompare(second.name))
})

const comparisonFeatures = computed(() => [
  ...coreFeatures.map((feature) => ({
    ...feature,
    includedInEveryPlan: true,
  })),
  ...planFeatures.value.map((feature) => ({
    ...feature,
    includedInEveryPlan: false,
  })),
])

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

function planIncludesFeature(plan, feature) {
  return feature.includedInEveryPlan || plan.features.some((item) => item.key === feature.key)
}
</script>

<template>
  <div class="bg-[#f7f8fb] text-slate-950">
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
                <form-button
                  :label="Number(plan.price) === 0 ? 'Start free' : 'Choose in app'"
                  :icon="Number(plan.price) === 0 ? 'pi pi-building' : 'pi pi-crown'"
                  class="w-full"
                />
              </RouterLink>
            </div>
          </div>
        </div>

        <div v-if="!loading && plans.length" class="mt-20">
          <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase text-brand-700">Compare plans</p>
            <h2 class="mt-4 text-3xl font-semibold leading-tight text-slate-950 sm:text-4xl">
              See every Worksyne feature by plan.
            </h2>
            <p class="mt-4 text-lg leading-8 text-slate-600">
              Core company operations are included in every plan. Paid plans add progressively more advanced workflow and forecasting tools.
            </p>
          </div>

          <div class="mt-9 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
              <div
                class="grid min-w-225"
                :style="{ gridTemplateColumns: `minmax(22rem, 1.4fr) repeat(${plans.length}, minmax(10rem, 0.6fr))` }"
              >
                <div class="sticky left-0 z-2 border-b border-r border-slate-200 bg-slate-50 p-5">
                  <div class="text-sm font-semibold uppercase tracking-[0.1em] text-slate-500">Features</div>
                  <div class="mt-2 text-sm text-slate-500">{{ comparisonFeatures.length }} capabilities compared</div>
                </div>

                <div
                  v-for="plan in plans"
                  :key="`heading-${plan.id}`"
                  class="border-b border-slate-200 bg-slate-50 p-5 text-center"
                >
                  <div class="text-lg font-semibold text-slate-950">{{ plan.name }}</div>
                  <div class="mt-2 text-sm font-medium text-brand-700">
                    {{ formatPrice(plan.price) }} / month
                  </div>
                </div>

                <template v-for="feature in comparisonFeatures" :key="feature.key">
                  <div class="sticky left-0 z-1 border-b border-r border-slate-200 bg-white p-5">
                    <div class="font-semibold text-slate-950">{{ feature.name }}</div>
                    <div class="mt-1 text-sm leading-6 text-slate-500">{{ feature.description }}</div>
                  </div>

                  <div
                    v-for="plan in plans"
                    :key="`${feature.key}-${plan.id}`"
                    class="grid min-h-24 place-items-center border-b border-slate-200 p-5"
                    :class="planIncludesFeature(plan, feature) ? 'bg-emerald-50/35' : 'bg-slate-50/50'"
                  >
                    <span
                      v-if="planIncludesFeature(plan, feature)"
                      class="grid h-10 w-10 place-items-center rounded-full bg-emerald-100 text-emerald-700"
                      :aria-label="`${feature.name} is included in ${plan.name}`"
                    >
                      <i class="pi pi-check text-lg" />
                    </span>
                    <span
                      v-else
                      class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-400"
                      :aria-label="`${feature.name} is not included in ${plan.name}`"
                    >
                      <i class="pi pi-times text-lg" />
                    </span>
                  </div>
                </template>

                <div class="sticky left-0 z-2 border-r border-slate-200 bg-brand-950 p-5 text-white">
                  <div class="font-semibold">Choose your plan</div>
                  <div class="mt-1 text-sm text-white/50">Sign in to manage your company subscription.</div>
                </div>

                <div
                  v-for="plan in plans"
                  :key="`action-${plan.id}`"
                  class="bg-brand-950 p-5"
                >
                  <RouterLink :to="{ name: 'sign-in' }">
                    <form-button
                      :label="Number(plan.price) === 0 ? 'Start free' : `Choose ${plan.name}`"
                      class="w-full"
                      severity="secondary"
                    />
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-4 flex flex-wrap gap-5 text-sm text-slate-500">
            <div class="flex items-center gap-2">
              <span class="grid h-6 w-6 place-items-center rounded-full bg-emerald-100 text-emerald-700">
                <i class="pi pi-check text-xs" />
              </span>
              Included
            </div>
            <div class="flex items-center gap-2">
              <span class="grid h-6 w-6 place-items-center rounded-full bg-slate-100 text-slate-400">
                <i class="pi pi-times text-xs" />
              </span>
              Not included
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
