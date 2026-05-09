import { createRouter, createWebHistory } from 'vue-router'
import { routes } from '../routes'

const authTokenKey = 'worksyne_auth_token'

export const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const isAuthenticated = Boolean(localStorage.getItem(authTokenKey))

  if (to.meta.requiresAuth && !isAuthenticated) {
    return {
      name: 'sign-in',
      query: {
        redirect: to.fullPath,
      },
    }
  }

  if (to.meta.guestOnly && isAuthenticated && typeof to.query.redirect === 'string') {
    return to.query.redirect
  }

  return true
})

export function registerRouter(app) {
  app.use(router)
}
