<script setup>
import { RouterLink } from 'vue-router'

const currentYear = new Date().getFullYear()
</script>

<template>
  <footer class="border-t border-slate-200 bg-slate-950 px-5 py-10 text-white lg:px-8">
    <div class="mx-auto grid max-w-7xl gap-8 md:grid-cols-[1fr_auto] md:items-end">
      <div>
        <RouterLink :to="{ name: 'landing' }" class="inline-flex items-center gap-3">
          <span class="grid h-10 w-10 place-items-center rounded-md bg-white text-brand-900">
            <i class="pi pi-briefcase text-lg" />
          </span>
          <span class="text-base font-semibold tracking-[0.08em]">WORKSYNE</span>
        </RouterLink>
        <p class="mt-4 max-w-xl text-sm leading-6 text-slate-400">
          Workforce operations, workstream planning, time tracking, and capacity forecasting in one secure workspace.
        </p>
      </div>

      <div class="grid gap-5 text-sm md:justify-items-end">
        <nav class="flex flex-wrap gap-x-6 gap-y-3 text-slate-300">
          <RouterLink :to="{ name: 'landing' }" class="hover:text-white">Home</RouterLink>
          <RouterLink :to="{ name: 'landing', hash: '#platform' }" class="hover:text-white">Platform</RouterLink>
          <RouterLink :to="{ name: 'about' }" class="hover:text-white">About us</RouterLink>
          <RouterLink :to="{ name: 'pricing' }" class="hover:text-white">Pricing</RouterLink>
          <RouterLink :to="{ name: 'contact' }" class="hover:text-white">Contact</RouterLink>
        </nav>
        <div class="text-slate-500">&copy; {{ currentYear }} Worksyne. All rights reserved.</div>
      </div>
    </div>
  </footer>
</template>
