import axios from 'axios'
import { inject } from 'vue'

export const httpKey = Symbol('http')

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'https://api.worksyne.local.test',
  headers: {
    Accept: 'application/json',
  },
})

export function registerHttp(app) {
  app.config.globalProperties.$http = http
  app.provide(httpKey, http)
}

export function useHttp() {
  const instance = inject(httpKey)

  if (!instance) {
    throw new Error('HTTP plugin is not registered.')
  }

  return instance
}
