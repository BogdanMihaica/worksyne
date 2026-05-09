<script setup>
import Card from 'primevue/card'
import Tag from 'primevue/tag'
</script>

<template>
  <section class="grid gap-5 xl:grid-cols-[1fr_22rem]">
    <Card class="border border-slate-200 shadow-sm">
      <template #title>
        <div class="flex items-center justify-between gap-4">
          <span class="text-2xl font-semibold text-slate-950">Dashboard</span>
          <Tag severity="info" value="Signed in" />
        </div>
      </template>

      <template #content>
        <p class="max-w-2xl leading-7 text-slate-600">
          Placeholder for further data
        </p>
      </template>
    </Card>

    <Card class="border border-slate-200 shadow-sm">
      <template #title>
        <span class="text-lg font-semibold text-slate-950">Today</span>
      </template>

      <template #content>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-slate-500">Open items</span>
            <span class="text-2xl font-semibold text-slate-950">18</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-slate-500">Notifications</span>
            <span class="text-2xl font-semibold text-slate-950">3</span>
          </div>
        </div>
      </template>
    </Card>
  </section>
</template>
