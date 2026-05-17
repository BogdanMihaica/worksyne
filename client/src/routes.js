import Landing from './views/Landing/Landing.vue'
import SignIn from './views/SignIn/SignIn.vue'
import SignedInLayout from './layouts/SignedInLayout.vue'
import Dashboard from './views/Dashboard/Dashboard.vue'
import Users from './views/Users/Users.vue'
import Companies from './views/Companies/Companies.vue'
import CompanyAdmins from './views/CompanyAdmins/CompanyAdmins.vue'
import SubscriptionPlans from './views/SubscriptionPlans/SubscriptionPlans.vue'
import Orders from './views/Orders/Orders.vue'

export const routes = [
  {
    path: '/',
    name: 'landing',
    component: Landing,
    meta: {
      guestOnly: true,
    },
  },
  {
    path: '/dashboard',
    name: 'signed-in',
    component: SignedInLayout,
    redirect: { name: 'dashboard' },
    meta: {
      requiresAuth: true,
    },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: Dashboard,
        meta: {
          requiresAuth: true,
          showInSidebar: true,
          label: 'Dashboard',
          icon: 'pi pi-home',
        },
      },
      {
        path: 'users',
        name: 'users',
        component: Users,
        meta: {
          requiresAuth: true,
          showInSidebar: true,
          label: 'Users',
          icon: 'pi pi-users',
        },
      },
      {
        path: 'companies',
        name: 'companies',
        component: Companies,
        meta: {
          requiresAuth: true,
          showInSidebar: true,
          label: 'Companies',
          icon: 'pi pi-building',
        },
      },
      {
        path: 'company-admins',
        name: 'company-admins',
        component: CompanyAdmins,
        meta: {
          requiresAuth: true,
          showInSidebar: true,
          label: 'Company Admins',
          icon: 'pi pi-user-edit',
        },
      },
      {
        path: 'subscription-plans',
        name: 'subscription-plans',
        component: SubscriptionPlans,
        meta: {
          requiresAuth: true,
          showInSidebar: true,
          label: 'Subscription Plans',
          icon: 'pi pi-credit-card',
        },
      },
      {
        path: 'orders',
        name: 'orders',
        component: Orders,
        meta: {
          requiresAuth: true,
          showInSidebar: true,
          label: 'Orders',
          icon: 'pi pi-receipt',
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
    redirect: { name: 'landing' },
  },
]
