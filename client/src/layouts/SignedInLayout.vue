<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterView, useRouter } from 'vue-router'
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import Menu from 'primevue/menu'
import { authStore } from '../stores/auth'

const router = useRouter()
const userMenu = ref()
const sidebarCollapsedKey = 'worksyne_sidebar_collapsed'
const isSidebarCollapsed = ref(localStorage.getItem(sidebarCollapsedKey) === 'true')

const userEmail = computed(() => authStore.userEmail.value)
const sidebarWidthClass = computed(() => (isSidebarCollapsed.value ? 'lg:w-20' : 'lg:w-68'))
const contentOffsetClass = computed(() => (isSidebarCollapsed.value ? 'lg:pl-20' : 'lg:pl-68'))

const menuItems = [
  {
    label: 'Sign out',
    icon: 'pi pi-sign-out',
    command: async () => {
      await authStore.signOut()
      router.push({ name: 'sign-in' })
    },
  },
]

onMounted(() => {
  if (!authStore.state.user) {
    authStore.fetchUser().catch(() => {
      router.push({ name: 'sign-in' })
    })
  }
})

function toggleUserMenu(event) {
  userMenu.value.toggle(event)
}

function toggleSidebar() {
  isSidebarCollapsed.value = !isSidebarCollapsed.value
  localStorage.setItem(sidebarCollapsedKey, String(isSidebarCollapsed.value))
}
</script>

<template>
  <div class="min-h-screen bg-[#f5f7fb] text-slate-950">
    <div
      class="fixed inset-y-0 left-0 z-20 hidden border-r border-slate-200 bg-white px-4 py-6 transition-[width] duration-200 lg:flex lg:flex-col"
      :class="sidebarWidthClass"
    >
      <div
        class="flex items-center gap-3 px-1"
        :class="isSidebarCollapsed ? 'justify-center' : 'justify-between'"
      >
        <div class="flex min-w-0 items-center gap-3">
          <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-brand-900 text-white">
            <i class="pi pi-briefcase text-lg" />
          </span>
          <span
            v-if="!isSidebarCollapsed"
            class="truncate text-lg font-semibold tracking-[0.08em] text-brand-900"
          >
            WORKSYNE
          </span>
        </div>

        <Button
          type="button"
          :icon="isSidebarCollapsed ? 'pi pi-angle-right' : 'pi pi-angle-left'"
          severity="secondary"
          text
          rounded
          :aria-label="isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          @click="toggleSidebar"
        />
      </div>

      <sidebar-routes :is-collapsed="isSidebarCollapsed" />
    </div>

    <div :class="contentOffsetClass" class="transition-[padding] duration-200">
      <div
        class="sticky top-0 z-10 flex h-18 items-center justify-between border-b border-slate-200 bg-white/90 px-5 backdrop-blur lg:px-8"
      >
        <div></div>
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
      </div>

      <div class="px-5 py-6 lg:px-8">
        <RouterView />
      </div>
    </div>
  </div>
</template>
