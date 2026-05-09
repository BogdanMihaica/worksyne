<script setup>
import { computed, ref } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import Menu from 'primevue/menu'
import { routes } from '../routes'

const route = useRoute()
const router = useRouter()
const userMenu = ref()

const userEmail = computed(() => {
  return localStorage.getItem('worksyne_user_email') || 'dummy@worksyne.local'
})

const sidebarRoutes = computed(() => {
  const signedInRoute = routes.find((item) => item.name === 'signed-in')

  return (signedInRoute?.children || []).filter((item) => item.meta?.showInSidebar)
})

const menuItems = [
  {
    label: 'Sign out',
    icon: 'pi pi-sign-out',
    command: () => {
      localStorage.setItem('worksyne_auth_token', 'false')
      localStorage.removeItem('worksyne_user_email')
      router.push({ name: 'sign-in' })
    },
  },
]

function toggleUserMenu(event) {
  userMenu.value.toggle(event)
}
</script>

<template>
  <div class="min-h-screen bg-[#f5f7fb] text-slate-950">
    <aside
      class="fixed inset-y-0 left-0 z-20 hidden w-68 border-r border-slate-200 bg-white px-5 py-6 lg:flex lg:flex-col"
    >
      <div class="flex items-center gap-3 px-2">
        <span class="grid h-10 w-10 place-items-center rounded-lg bg-brand-900 text-white">
          <i class="pi pi-briefcase text-lg" />
        </span>
        <span class="text-lg font-semibold tracking-[0.08em] text-brand-900">WORKSYNE</span>
      </div>

      <nav class="mt-8 space-y-1">
        <RouterLink
          v-for="item in sidebarRoutes"
          :key="item.name"
          :to="{ name: item.name }"
          class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
          :class="{
            'bg-brand-50 text-brand-900 ring-1 ring-inset ring-brand-100': route.name === item.name,
          }"
        >
          <i :class="[item.meta.icon, 'text-base']" />
          <span>{{ item.meta.label }}</span>
        </RouterLink>
      </nav>
    </aside>

    <div class="lg:pl-68">
      <header
        class="sticky top-0 z-10 flex h-18 items-center justify-between border-b border-slate-200 bg-white/90 px-5 backdrop-blur lg:px-8"
      >
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-900">
            Workspace
          </p>
          <h1 class="mt-1 text-xl font-semibold text-slate-950">
            {{ route.meta.label || 'Dashboard' }}
          </h1>
        </div>

        <div class="flex items-center gap-3">
          <Button
            type="button"
            icon="pi pi-bell"
            severity="secondary"
            text
            rounded
            aria-label="Notifications"
          />

          <Button
            type="button"
            text
            class="gap-3 rounded-lg px-2 py-2 text-slate-700"
            aria-haspopup="true"
            aria-controls="user-menu"
            @click="toggleUserMenu"
          >
            <Avatar icon="pi pi-user" shape="circle" class="bg-brand-50 text-brand-900" />
            <span class="hidden max-w-56 truncate text-sm font-medium sm:inline text-brand-600">
              {{ userEmail }}
            </span>
            <i class="pi pi-chevron-down text-xs text-slate-400" />
          </Button>
          <Menu id="user-menu" ref="userMenu" :model="menuItems" popup />
        </div>
      </header>

      <main class="px-5 py-6 lg:px-8">
        <RouterView />
      </main>
    </div>
  </div>
</template>
