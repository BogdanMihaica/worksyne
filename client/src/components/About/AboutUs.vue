<script setup>
import { RouterLink } from 'vue-router'

const functions = [
  {
    icon: 'pi pi-building',
    title: 'Company and team management',
    text: 'Create a company workspace, add users, assign company administrators or workers, manage approval status, and keep external employee references alongside each profile.',
  },
  {
    icon: 'pi pi-sitemap',
    title: 'Workstreams and seniority',
    text: 'Organize work into separate workstreams and assign people to the areas they actually work in. Each assignment can include a seniority level, giving managers a clearer picture of available skills.',
  },
  {
    icon: 'pi pi-list-check',
    title: 'Work unit logging',
    text: 'Workers can record completed units against an assigned workstream, choose the day the work was completed, and add a reference code or note. Managers can review and filter activity across the company.',
  },
  {
    icon: 'pi pi-clock',
    title: 'Live time tracking',
    text: 'Workers can start and stop a work session, take breaks, resume work, and see their current status. Company administrators can monitor whether team members are working, on a break, or offline.',
  },
  {
    icon: 'pi pi-calendar-clock',
    title: 'Time off and timesheets',
    text: 'Workers can request time off and follow its approval status. Company administrators can review requests, approve or reject them, and view worked time and approved leave together in the company timesheet.',
  },
  {
    icon: 'pi pi-gauge',
    title: 'Capacity models',
    text: 'Define expected units per hour for each workstream and seniority level. These values turn team assignments and recorded working time into a practical view of available operational capacity.',
  },
  {
    icon: 'pi pi-chart-bar',
    title: 'Workload forecasting',
    text: 'Worksyne uses historical work-unit activity, working patterns, time off, seniority assignments, and capacity models to estimate upcoming demand, available capacity, and possible shortages or surpluses.',
  },
  {
    icon: 'pi pi-bell',
    title: 'Operational notifications',
    text: 'Company administrators can send messages to selected team members. Worksyne also creates relevant alerts around time-off decisions and unusually long periods of work or breaks.',
  },
]

const roles = [
  {
    title: 'For company administrators',
    text: 'Set up the company, manage users and workstreams, review work logs, handle time-off requests, configure capacity, monitor operational status, and use forecasts to plan ahead.',
  },
  {
    title: 'For workers',
    text: 'Log completed work, track working time and breaks, request time off, view timesheet activity, receive notifications, and keep personal company information in one place.',
  },
]
</script>

<template>
  <div class="bg-[#f7f8fb] text-slate-950">
    <div class="px-5 py-16 lg:px-8 lg:py-24">
      <div class="mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-[1fr_0.8fr]">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.12em] text-brand-700">About Worksyne</p>
          <h1 class="mt-5 max-w-4xl text-4xl font-semibold leading-tight sm:text-5xl lg:text-6xl">
            A practical workspace for managing people, work, time, and capacity.
          </h1>
          <p class="mt-6 max-w-3xl text-lg leading-8 text-slate-600">
            Worksyne is built for companies that need a clear view of who is working, what is being completed, where time is going, and whether the team has enough capacity for upcoming demand.
          </p>
          <p class="mt-4 max-w-3xl text-lg leading-8 text-slate-600">
            Instead of keeping employee assignments, work records, leave requests, timers, and planning data in separate tools, Worksyne connects them inside one company workspace.
          </p>

          <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <RouterLink :to="{ name: 'pricing' }">
              <form-button label="View plans" icon="pi pi-arrow-right" icon-position="right" class="w-full sm:w-auto" size="lg" />
            </RouterLink>
            <RouterLink :to="{ name: 'contact' }">
              <form-button label="Contact us" icon="pi pi-envelope" severity="secondary" class="w-full sm:w-auto" size="lg" />
            </RouterLink>
          </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.12)] sm:p-8">
          <div class="flex items-center gap-3 border-b border-slate-200 pb-5">
            <span class="grid h-12 w-12 place-items-center rounded-lg bg-brand-900 text-white">
              <i class="pi pi-briefcase text-xl" />
            </span>
            <div>
              <div class="font-semibold tracking-[0.08em] text-brand-900">WORKSYNE</div>
              <div class="mt-1 text-sm text-slate-500">Company operations in one place</div>
            </div>
          </div>

          <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <div class="rounded-lg bg-slate-50 p-4">
              <i class="pi pi-users text-brand-700" />
              <div class="mt-3 font-semibold">People</div>
              <div class="mt-1 text-sm leading-6 text-slate-500">Roles, assignments, status, and seniority.</div>
            </div>
            <div class="rounded-lg bg-slate-50 p-4">
              <i class="pi pi-clipboard text-brand-700" />
              <div class="mt-3 font-semibold">Work</div>
              <div class="mt-1 text-sm leading-6 text-slate-500">Units, workstreams, references, and notes.</div>
            </div>
            <div class="rounded-lg bg-slate-50 p-4">
              <i class="pi pi-clock text-brand-700" />
              <div class="mt-3 font-semibold">Time</div>
              <div class="mt-1 text-sm leading-6 text-slate-500">Sessions, breaks, timesheets, and leave.</div>
            </div>
            <div class="rounded-lg bg-slate-50 p-4">
              <i class="pi pi-chart-line text-brand-700" />
              <div class="mt-3 font-semibold">Planning</div>
              <div class="mt-1 text-sm leading-6 text-slate-500">Capacity models, forecasts, and alerts.</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="border-y border-slate-200 bg-white px-5 py-16 lg:px-8 lg:py-20">
      <div class="mx-auto max-w-7xl">
        <div class="max-w-3xl">
          <p class="text-sm font-semibold uppercase tracking-[0.12em] text-brand-700">What Worksyne does</p>
          <h2 class="mt-4 text-3xl font-semibold leading-tight sm:text-4xl">
            The operational workflows a company needs every day.
          </h2>
          <p class="mt-5 text-lg leading-8 text-slate-600">
            Each part of Worksyne is connected to the same company, users, and workstreams, so records can support both daily operations and longer-term planning.
          </p>
        </div>

        <div class="mt-10 grid gap-5 md:grid-cols-2">
          <div
            v-for="item in functions"
            :key="item.title"
            class="rounded-xl border border-slate-200 bg-[#f7f8fb] p-6 sm:p-7"
          >
            <span class="grid h-11 w-11 place-items-center rounded-lg bg-white text-brand-800 shadow-sm">
              <i :class="[item.icon, 'text-lg']" />
            </span>
            <h3 class="mt-5 text-xl font-semibold">{{ item.title }}</h3>
            <p class="mt-3 leading-7 text-slate-600">{{ item.text }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="px-5 py-16 lg:px-8 lg:py-20">
      <div class="mx-auto max-w-7xl">
        <div class="grid gap-8 lg:grid-cols-[0.7fr_1.3fr]">
          <div>
            <p class="text-sm font-semibold uppercase tracking-[0.12em] text-brand-700">Built around real roles</p>
            <h2 class="mt-4 text-3xl font-semibold leading-tight sm:text-4xl">
              The right tools for managers and workers.
            </h2>
            <p class="mt-5 leading-7 text-slate-600">
              Access is based on the user’s role and company subscription. Administrators receive management and planning tools, while workers get focused self-service workflows.
            </p>
          </div>

          <div class="grid gap-5 sm:grid-cols-2">
            <div
              v-for="role in roles"
              :key="role.title"
              class="rounded-xl border border-slate-200 bg-white p-7 shadow-sm"
            >
              <h3 class="text-xl font-semibold">{{ role.title }}</h3>
              <p class="mt-4 leading-7 text-slate-600">{{ role.text }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-brand-950 px-5 py-16 text-white lg:px-8 lg:py-20">
      <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1fr_auto] lg:items-center">
        <div class="max-w-3xl">
          <p class="text-sm font-semibold uppercase tracking-[0.12em] text-brand-300">Why it is connected</p>
          <h2 class="mt-4 text-3xl font-semibold leading-tight sm:text-4xl">
            Daily records become useful planning information.
          </h2>
          <p class="mt-5 text-lg leading-8 text-white/60">
            A logged work unit is more useful when it is connected to a worker, a workstream, a date, and a capacity model. Worksyne keeps those relationships together so managers can move from recording activity to understanding demand and planning the team.
          </p>
        </div>

        <RouterLink :to="{ name: 'contact' }">
          <form-button label="Talk to us" icon="pi pi-send" severity="secondary" size="lg" class="w-full lg:w-auto" />
        </RouterLink>
      </div>
    </div>
  </div>
</template>
