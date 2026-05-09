import SignIn from './views/SignIn/SignIn.vue'
import SignedInLayout from './layouts/SignedInLayout.vue'
import Dashboard from './views/Dashboard/Dashboard.vue'

export const routes = [
  {
    path: '/',
    name: 'signed-in',
    component: SignedInLayout,
    redirect: { name: 'dashboard' },
    meta: {
      requiresAuth: true,
    },
    children: [
      {
        path: 'dashboard',
        name: 'dashboard',
        component: Dashboard,
        meta: {
          requiresAuth: true,
          showInSidebar: true,
          label: 'Dashboard',
          icon: 'pi pi-home',
        },
      },
    ],
  },
  {
    path: '/sign-in',
    name: 'sign-in',
    component: SignIn,
    meta: {
      guestOnly: true,
    },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: { name: 'dashboard' },
  },
]
