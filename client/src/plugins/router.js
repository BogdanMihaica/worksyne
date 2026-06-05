import { createRouter, createWebHistory } from 'vue-router'
import { routes } from '../routes'
import { authStore } from '../stores/auth'

export const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  if (authStore.isAuthenticated.value && !authStore.state.user) {
    try {
      await authStore.fetchUser()
    } catch {
      if (to.meta.requiresAuth) {
        return {
          name: 'sign-in',
          query: {
            redirect: to.fullPath,
          },
        }
      }
    }
  }

  const isAuthenticated = authStore.isAuthenticated.value

  if (to.meta.requiresAuth && !isAuthenticated) {
    return {
      name: 'sign-in',
      query: {
        redirect: to.fullPath,
      },
    }
  }

  if (to.meta.roles && !to.meta.roles.includes(authStore.userRole.value)) {
    return { name: 'dashboard' }
  }

  if (to.meta.feature && !authStore.hasFeature(to.meta.feature)) {
    return { name: 'upgrade' }
  }

  if (to.meta.guestOnly && isAuthenticated) {
    return typeof to.query.redirect === 'string' ? to.query.redirect : { name: 'dashboard' }
  }

  return true
})

export function registerRouter(app) {
  app.use(router)
}
