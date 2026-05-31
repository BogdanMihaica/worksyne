import { inject } from 'vue'

export const stringsKey = Symbol('strings')

export const strings = {
  upperCase(value) {
    return String(value ?? '')
      .trim()
      .replace(/[\s-]+/g, '_')
      .replace(/([a-z0-9])([A-Z])/g, '$1_$2')
      .toUpperCase()
  },
}

export function registerStrings(app) {
  app.config.globalProperties.$strings = strings
  app.provide(stringsKey, strings)
}

export function useStrings() {
  const helpers = inject(stringsKey)

  if (!helpers) {
    throw new Error('String helpers are not registered.')
  }

  return helpers
}
