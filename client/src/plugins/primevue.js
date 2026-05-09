import Aura from '@primeuix/themes/aura'
import PrimeVue from 'primevue/config'

export function registerPrimeVue(app) {
  app.use(PrimeVue, {
    theme: {
      preset: Aura,
      options: {
        darkModeSelector: false,
      },
    },
  })
}
