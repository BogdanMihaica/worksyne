import Landing from './views/Landing/Landing.vue'
import SignIn from './views/SignIn/SignIn.vue'
import SignedInLayout from './layouts/SignedInLayout.vue'
import Dashboard from './views/Dashboard/Dashboard.vue'
import Users from './views/Users/Users.vue'
import Companies from './views/Companies/Companies.vue'
import CompanyAdmins from './views/CompanyAdmins/CompanyAdmins.vue'
import SubscriptionPlans from './views/SubscriptionPlans/SubscriptionPlans.vue'
import Orders from './views/Orders/Orders.vue'
import Company from './views/Company/Company.vue'
import Workstreams from './views/Workstreams/Workstreams.vue'
import OrderHistory from './views/OrderHistory/OrderHistory.vue'
import Planner from './views/Planner/Planner.vue'
import Timesheet from './views/Timesheet/Timesheet.vue'
import Analytics from './views/Analytics/Analytics.vue'

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
        },
      },
      {
        path: 'users',
        name: 'users',
        component: Users,
        meta: {
          requiresAuth: true,
          roles: ['admin'],
        },
      },
      {
        path: 'companies',
        name: 'companies',
        component: Companies,
        meta: {
          requiresAuth: true,
          roles: ['admin'],
        },
      },
      {
        path: 'company-admins',
        name: 'company-admins',
        component: CompanyAdmins,
        meta: {
          requiresAuth: true,
          roles: ['admin'],
        },
      },
      {
        path: 'subscription-plans',
        name: 'subscription-plans',
        component: SubscriptionPlans,
        meta: {
          requiresAuth: true,
          roles: ['admin'],
        },
      },
      {
        path: 'orders',
        name: 'orders',
        component: Orders,
        meta: {
          requiresAuth: true,
          roles: ['admin'],
        },
      },
      {
        path: 'company',
        name: 'company',
        component: Company,
        meta: {
          requiresAuth: true,
          roles: ['company_admin', 'worker'],
        },
      },
      {
        path: 'workstreams',
        name: 'workstreams',
        component: Workstreams,
        meta: {
          requiresAuth: true,
          roles: ['company_admin'],
        },
      },
      {
        path: 'order-history',
        name: 'order-history',
        component: OrderHistory,
        meta: {
          requiresAuth: true,
          roles: ['company_admin', 'admin'],
        },
      },
      {
        path: 'planner',
        name: 'planner',
        component: Planner,
        meta: {
          requiresAuth: true,
          roles: ['company_admin'],
        },
      },
      {
        path: 'timesheet',
        name: 'timesheet',
        component: Timesheet,
        meta: {
          requiresAuth: true,
          roles: ['company_admin', 'team_lead', 'worker'],
        },
      },
      {
        path: 'analytics',
        name: 'analytics',
        component: Analytics,
        meta: {
          requiresAuth: true,
          roles: ['company_admin', 'admin'],
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
