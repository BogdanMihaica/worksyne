<script setup>
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { routes } from '../../routes'

defineProps({
  isCollapsed: {
    type: Boolean,
    default: false,
  },
})

const route = useRoute()

const sidebarRoutes = computed(() => {
  const signedInRoute = routes.find((item) => item.name === 'signed-in')

  return (signedInRoute?.children || []).filter((item) => item.meta?.showInSidebar)
})
</script>

<template>
  <div class="mt-8 space-y-1">
    <RouterLink
      v-for="item in sidebarRoutes"
      :key="item.name"
      :to="{ name: item.name }"
      class="flex min-h-10 items-center rounded-lg text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
      :class="[
        isCollapsed ? 'justify-center px-2' : 'gap-3 px-3 py-2.5',
        route.name === item.name ? 'bg-brand-50 text-brand-900 ring-1 ring-inset ring-brand-100' : '',
      ]"
      :aria-label="item.meta.label"
      :title="isCollapsed ? item.meta.label : undefined"
    >
      <i :class="[item.meta.icon, 'text-base']" />
      <span v-if="!isCollapsed">{{ item.meta.label }}</span>
    </RouterLink>
  </div>
</template>
