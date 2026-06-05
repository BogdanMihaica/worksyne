<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Menu from 'primevue/menu'
import Popover from 'primevue/popover'
import { authStore } from '../stores/auth'
import { useHttp } from '../plugins/http'
import { useAppToast } from '../plugins/toast'

const router = useRouter()
const route = useRoute()
const http = useHttp()
const toast = useAppToast()
const userMenu = ref()
const notificationPopover = ref()
const notifications = ref([])
const unreadNotificationsCount = ref(0)
const loadingNotifications = ref(false)
const markingAllRead = ref(false)
const selectedNotification = ref(null)
const isNotificationModalOpen = ref(false)
const sidebarCollapsedKey = 'worksyne_sidebar_collapsed'
const isSidebarCollapsed = ref(localStorage.getItem(sidebarCollapsedKey) === 'true')

const userEmail = computed(() => authStore.userEmail.value)
const sidebarWidthClass = computed(() => (isSidebarCollapsed.value ? 'lg:w-20' : 'lg:w-68'))
const contentOffsetClass = computed(() => (isSidebarCollapsed.value ? 'lg:pl-20' : 'lg:pl-68'))
const showBackButton = computed(() => route.name !== 'dashboard')
const canUseNotifications = computed(() => authStore.hasFeature('notifications'))
const showUpgradeButton = computed(() => authStore.userRole.value === 'company_admin')

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
    authStore.fetchUser()
      .then(() => loadNotifications())
      .catch(() => {
        router.push({ name: 'sign-in' })
      })
    return
  }

  loadNotifications()
})

function toggleUserMenu(event) {
  userMenu.value.toggle(event)
}

function toggleSidebar() {
  isSidebarCollapsed.value = !isSidebarCollapsed.value
  localStorage.setItem(sidebarCollapsedKey, String(isSidebarCollapsed.value))
}

function goBack() {
  if (window.history.state?.back) {
    router.back()
    return
  }

  router.push({ name: 'dashboard' })
}

async function loadNotifications() {
  loadingNotifications.value = true

  try {
    const { data } = await http.get('/api/notifications')

    notifications.value = data.notifications || []
    unreadNotificationsCount.value = data.unread_count || 0
  } catch {
    toast({ type: 'error', message: 'Unable to load notifications.' })
  } finally {
    loadingNotifications.value = false
  }
}

function toggleNotifications(event) {
  notificationPopover.value.toggle(event)

  if (!notifications.value.length) {
    loadNotifications()
  }
}

async function openNotification(notification) {
  selectedNotification.value = notification
  isNotificationModalOpen.value = true

  if (notification.is_read) {
    return
  }

  try {
    const { data } = await http.patch(`/api/notifications/${notification.id}/read`)

    unreadNotificationsCount.value = data.unread_count || 0
    notifications.value = notifications.value.map((item) => (
      item.id === notification.id ? data.notification : item
    ))
    selectedNotification.value = data.notification
  } catch {
    toast({ type: 'error', message: 'Unable to mark notification as read.' })
  }
}

async function markAllNotificationsRead() {
  markingAllRead.value = true

  try {
    const { data } = await http.patch('/api/notifications/read-all')

    unreadNotificationsCount.value = data.unread_count || 0
    notifications.value = notifications.value.map((notification) => ({
      ...notification,
      is_read: true,
    }))
  } catch {
    toast({ type: 'error', message: 'Unable to mark notifications as read.' })
  } finally {
    markingAllRead.value = false
  }
}

function formatNotificationDate(value) {
  if (!value) {
    return ''
  }

  return new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value))
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
        class="sticky top-0 z-10 flex min-h-18 flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white/90 px-5 py-3 backdrop-blur lg:px-8"
      >
        <div class="text-lg font-medium">Admin Portal</div>
        <div class="flex flex-wrap items-center justify-end gap-3">
          <work-timer />

          <RouterLink v-if="showUpgradeButton" :to="{ name: 'upgrade' }">
            <Button
              type="button"
              icon="pi pi-crown"
              label="Upgrade"
              size="small"
              class="bg-brand-900! text-white!"
            />
          </RouterLink>

          <div v-if="canUseNotifications" class="relative">
            <Button
              type="button"
              icon="pi pi-bell"
              severity="secondary"
              text
              rounded
              aria-label="Notifications"
              @click="toggleNotifications"
            />
            <span
              v-if="unreadNotificationsCount > 0"
              class="absolute -right-1 -top-1 grid min-h-5 min-w-5 place-items-center rounded-full bg-red-600 px-1.5 text-[11px] font-bold leading-none text-white ring-2 ring-white"
            >
              {{ unreadNotificationsCount > 99 ? '99+' : unreadNotificationsCount }}
            </span>
          </div>
          <Popover ref="notificationPopover">
            <div class="w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-lg bg-white">
              <div class="border-b border-slate-100 px-4 py-3">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-sm font-semibold text-slate-950">Notifications</div>
                    <div class="mt-1 text-xs text-slate-500">
                      {{ unreadNotificationsCount }} unread message{{ unreadNotificationsCount === 1 ? '' : 's' }}
                    </div>
                  </div>
                  <Button
                    type="button"
                    label="Mark all as read"
                    size="small"
                    text
                    :disabled="unreadNotificationsCount === 0"
                    :loading="markingAllRead"
                    class="shrink-0"
                    @click="markAllNotificationsRead"
                  />
                </div>
              </div>

              <div v-if="loadingNotifications" class="px-4 py-8 text-center text-sm text-slate-500">
                Loading notifications...
              </div>

              <div v-else-if="!notifications.length" class="px-4 py-8 text-center">
                <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-500">
                  <i class="pi pi-bell" />
                </div>
                <div class="mt-3 text-sm font-semibold text-slate-900">Nothing waiting</div>
                <div class="mt-1 text-xs text-slate-500">New notifications will appear here.</div>
              </div>

              <div v-else class="max-h-100 overflow-y-auto p-2">
                <button
                  v-for="notification in notifications"
                  :key="notification.id"
                  type="button"
                  class="flex w-full gap-3 rounded-md px-3 py-3 text-left transition hover:bg-slate-50"
                  @click="openNotification(notification)"
                >
                  <span
                    class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full"
                    :class="notification.is_read ? 'bg-slate-200' : 'bg-red-500 shadow-[0_0_0_4px_rgba(239,68,68,0.12)]'"
                  />
                  <span class="min-w-0 flex-1">
                    <span class="flex items-center justify-between gap-3">
                      <span class="truncate text-sm font-semibold text-slate-950">{{ notification.from_name }}</span>
                      <span class="shrink-0 text-[11px] text-slate-400">{{ formatNotificationDate(notification.created_at) }}</span>
                    </span>
                    <span class="mt-1 line-clamp-2 block text-xs leading-5 text-slate-500">
                      {{ notification.message }}
                    </span>
                  </span>
                </button>
              </div>
            </div>
          </Popover>
          
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
        <Button
          v-if="showBackButton"
          type="button"
          icon="pi pi-arrow-left"
          label="Back"
          severity="secondary"
          size="small"
          text
          class="mb-5"
          @click="goBack"
        />

        <RouterView />
      </div>
    </div>

    <Dialog
      v-model:visible="isNotificationModalOpen"
      modal
      :style="{ width: 'min(520px, calc(100vw - 32px))' }"
      :pt="{ header: { class: '!pb-0' } }"
    >
      <template #header>
        <div class="flex items-center gap-3">
          <div class="grid h-10 w-10 place-items-center rounded-full bg-brand-50 text-brand-900">
            <i :class="selectedNotification?.from_id ? 'pi pi-user' : 'pi pi-cog'" />
          </div>
          <div>
            <div class="text-base font-semibold text-slate-950">
              {{ selectedNotification?.from_name || 'System' }}
            </div>
            <div class="text-xs text-slate-500">
              {{ selectedNotification?.from_email || 'Worksyne notification' }}
            </div>
          </div>
        </div>
      </template>

      <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="text-xs font-semibold uppercase text-slate-500">
          Message
        </div>
        <div class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-800">
          {{ selectedNotification?.message }}
        </div>
      </div>

      <div class="mt-4 flex items-center justify-between gap-3 text-xs text-slate-500">
        <span>{{ formatNotificationDate(selectedNotification?.created_at) }}</span>
        <Button
          type="button"
          label="Close"
          size="small"
          severity="secondary"
          text
          @click="isNotificationModalOpen = false"
        />
      </div>
    </Dialog>
  </div>
</template>
