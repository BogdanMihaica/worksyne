<script setup>
import { RouterLink } from 'vue-router'

const contactEmail = 'hello@worksyne.local.test'
const registerHref = `mailto:${contactEmail}?subject=Register%20my%20company%20on%20Worksyne`

const metrics = [
  { label: 'Active operators', value: '4,280' },
  { label: 'Coverage reliability', value: '98.7%' },
  { label: 'Avg. response time', value: '9m' },
]

const capabilities = [
  {
    icon: 'pi pi-building',
    title: 'Company control',
    text: 'Centralize company ownership, memberships, roles, approval status, and operating structure.',
  },
  {
    icon: 'pi pi-sitemap',
    title: 'Workstream visibility',
    text: 'Track teams across tasks, calls, vendors, units completed, and workstream-specific seniority.',
  },
  {
    icon: 'pi pi-shield',
    title: 'Secure operations',
    text: 'Protect every workflow with bearer-token authentication and guarded API access.',
  },
]

const rows = [
  { team: 'Tasks', status: 'Fully staffed', users: '18', load: '74%' },
  { team: 'Calls', status: 'Needs lead review', users: '11', load: '86%' },
  { team: 'Vendors', status: 'Pending approvals', users: '7', load: '42%' },
]
</script>

<template>
  <div class="bg-[#f7f8fb] text-slate-950">
    <div class="bg-[#f7f8fb] px-5 py-10 lg:px-8 lg:py-16">
      <div class="mx-auto grid max-w-7xl items-center gap-8 lg:grid-cols-[0.92fr_1.08fr]">
        <div class="py-4">
          <p class="text-sm font-semibold uppercase text-brand-700">
            Enterprise workforce orchestration
          </p>
          <h1 class="mt-5 max-w-3xl text-4xl font-semibold leading-tight text-slate-950 sm:text-5xl lg:text-6xl">
            Operate distributed teams with executive-grade control.
          </h1>
          <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
            Worksyne gives companies a secure operating layer for teams, workstreams, seniority, time off, and repeatable daily execution.
          </p>

          <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <RouterLink :to="{ name: 'sign-in' }">
              <form-button label="Sign in" icon="pi pi-sign-in" class="w-full sm:w-auto" size="lg" />
            </RouterLink>
            <a :href="registerHref">
              <form-button label="Register your company" icon="pi pi-building" severity="secondary" class="w-full sm:w-auto" size="lg" />
            </a>
            <RouterLink :to="{ name: 'contact' }">
              <form-button label="Contact" icon="pi pi-envelope" severity="ternary" transparent class="w-full sm:w-auto" size="lg" />
            </RouterLink>
          </div>

          <div class="mt-10 grid gap-3 sm:grid-cols-3">
            <div
              v-for="metric in metrics"
              :key="metric.label"
              class="rounded-md border border-slate-200 bg-white p-4"
            >
              <p class="text-2xl font-semibold text-slate-950">{{ metric.value }}</p>
              <p class="mt-1 text-sm text-slate-500">{{ metric.label }}</p>
            </div>
          </div>
        </div>

        <div class="rounded-md border border-slate-200 bg-slate-950 p-3 shadow-[0_28px_80px_rgba(15,23,42,0.22)] sm:p-5">
          <div class="rounded-md border border-white/10 bg-white/4">
            <div class="flex flex-col gap-4 border-b border-white/10 p-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-white/50">Operations command</p>
                <h2 class="mt-1 text-xl font-semibold text-white">Company overview</h2>
              </div>
              <div class="flex items-center gap-2 rounded-md bg-emerald-400/10 px-3 py-2 text-sm text-emerald-200">
                <span class="h-2 w-2 rounded-full bg-emerald-300" />
                Live coverage
              </div>
            </div>

            <div class="grid gap-3 p-4 sm:grid-cols-3">
              <div class="rounded-md border border-white/10 bg-white/6] p-4">
                <p class="text-sm text-white/45">Workers</p>
                <p class="mt-3 text-3xl font-semibold text-white">23</p>
              </div>
              <div class="rounded-md border border-white/10 bg-white/6 p-4">
                <p class="text-sm text-white/45">Workstreams</p>
                <p class="mt-3 text-3xl font-semibold text-white">3</p>
              </div>
              <div class="rounded-md border border-white/10 bg-white/6 p-4">
                <p class="text-sm text-white/45">New units</p>
                <p class="mt-3 text-3xl font-semibold text-white">16</p>
              </div>
            </div>

            <div class="border-t border-white/10 p-4">
              <div class="grid gap-3">
                <div
                  v-for="row in rows"
                  :key="row.team"
                  class="grid gap-3 rounded-md border border-white/10 bg-white/4.5 p-4 sm:grid-cols-[1fr_auto_auto_auto] sm:items-center"
                >
                  <div>
                    <p class="font-medium text-white">{{ row.team }}</p>
                    <p class="mt-1 text-sm text-white/45">{{ row.status }}</p>
                  </div>
                  <p class="text-sm text-white/55">{{ row.users }} users</p>
                  <p class="text-sm text-white/55">{{ row.load }} load</p>
                  <form-button icon="pi pi-arrow-right" severity="secondary" transparent aria-label="View workstream" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="platform" class="bg-white px-5 py-16 lg:px-8">
      <div class="mx-auto max-w-7xl">
        <div class="max-w-2xl">
          <p class="text-sm font-semibold uppercase text-brand-700">Platform</p>
          <h2 class="mt-3 text-3xl font-semibold text-slate-950 sm:text-4xl">
            Built for operating teams, not landing-page demos.
          </h2>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-3">
          <div
            v-for="capability in capabilities"
            :key="capability.title"
            class="rounded-md border border-slate-200 bg-white p-6"
          >
            <span class="grid h-11 w-11 place-items-center rounded-md bg-brand-50 text-brand-800">
              <i :class="[capability.icon, 'text-lg']" />
            </span>
            <h3 class="mt-5 text-xl font-semibold">{{ capability.title }}</h3>
            <p class="mt-3 leading-7 text-slate-600">{{ capability.text }}</p>
          </div>
        </div>
      </div>
    </div>

    <div id="contact" class="border-t border-slate-200 bg-[#eef1f6] px-5 py-14 lg:px-8">
      <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
        <div>
          <p class="text-sm font-semibold uppercase text-brand-700">Contact</p>
          <h2 class="mt-3 text-3xl font-semibold text-slate-950">
            Register your company or talk to us.
          </h2>
          <p class="mt-3 max-w-2xl leading-7 text-slate-600">
            Share your company structure and rollout needs. We will help you map the first Worksyne workspace.
          </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row lg:justify-end">
          <a :href="registerHref">
            <form-button label="Register your company" icon="pi pi-building" class="w-full sm:w-auto" />
          </a>
          <RouterLink :to="{ name: 'contact' }">
            <form-button label="Contact" icon="pi pi-send" severity="secondary" class="w-full sm:w-auto" />
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>
