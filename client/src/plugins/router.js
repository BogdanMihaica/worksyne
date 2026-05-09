import { createRouter, createWebHistory } from 'vue-router'
import { routes } from '../routes'

const authTokenKey = 'worksyne_auth_token'

function isSignedIn() {
  return localStorage.getItem(authTokenKey) === 'true'
}

export const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const isAuthenticated = isSignedIn()

  if (to.meta.requiresAuth && !isAuthenticated) {
    return {
      name: 'sign-in',
      query: {
        redirect: to.fullPath,
      },
    }
  }

  if (to.meta.guestOnly && isAuthenticated) {
    return typeof to.query.redirect === 'string' ? to.query.redirect : { name: 'dashboard' }
  }

  return true
})

export function registerRouter(app) {
  app.use(router)
}
