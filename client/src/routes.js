import SignIn from './views/SignIn.vue'

export const routes = [
  {
    path: '/',
    redirect: { name: 'sign-in' },
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
    redirect: { name: 'sign-in' },
  },
]
