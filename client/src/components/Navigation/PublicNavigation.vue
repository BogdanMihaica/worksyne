<script setup>
import { RouterLink } from 'vue-router'

const contactEmail = 'hello@worksyne.local.test'
const registerHref = `mailto:${contactEmail}?subject=Register%20my%20company%20on%20Worksyne`
</script>

<template>
  <header class="border-b border-slate-200 bg-white/95">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 lg:px-8">
      <RouterLink :to="{ name: 'landing' }" class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-md bg-brand-900 text-white">
          <i class="pi pi-briefcase text-lg" />
        </span>
        <span class="text-base font-semibold tracking-[0.08em] text-brand-900">WORKSYNE</span>
      </RouterLink>

      <nav class="hidden items-center gap-7 text-sm font-medium text-slate-600 md:flex">
        <RouterLink :to="{ name: 'landing', hash: '#platform' }" class="hover:text-slate-950">
          Platform
        </RouterLink>
        <RouterLink :to="{ name: 'about' }" class="hover:text-slate-950">
          About us
        </RouterLink>
        <RouterLink :to="{ name: 'pricing' }" class="hover:text-slate-950">
          Pricing
        </RouterLink>
        <RouterLink :to="{ name: 'contact' }" class="hover:text-slate-950">
          Contact
        </RouterLink>
      </nav>

      <div class="flex items-center gap-2">
        <RouterLink :to="{ name: 'sign-in' }">
          <form-button label="Sign in" severity="secondary" transparent />
        </RouterLink>
        <a :href="registerHref" class="hidden sm:inline-flex">
          <form-button
            label="Register company"
            icon="pi pi-arrow-right"
            icon-position="right"
          />
        </a>
      </div>
    </div>
  </header>
</template>
