<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <RouterLink
    :to="{ name: 'landing' }"
    class="absolute left-5 top-5 z-10 inline-flex h-10 items-center gap-2 rounded-md border border-white/70 bg-white/85 px-4 text-sm font-semibold text-brand-900 shadow-sm backdrop-blur transition hover:bg-white sm:left-8 sm:top-8"
  >
    <i class="pi pi-home" />
    Home
  </RouterLink>
</template>
