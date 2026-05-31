import { definePreset } from '@primeuix/themes'
import Aura from '@primeuix/themes/aura'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'

const WorksynePreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: '#f3f4ff',
      100: '#e7e9ff',
      200: '#cfd4ff',
      300: '#aeb6ff',
      400: '#8790ff',
      500: '#5d62e8',
      600: '#000125',
      700: '#000125',
      800: '#000015',
      900: '#000125',
      950: '#000015',
    },
  },
})

export function registerPrimeVue(app) {
  app.use(PrimeVue, {
    theme: {
      preset: WorksynePreset,
      options: {
        darkModeSelector: false,
      },
    },
  })
  app.use(ToastService)
}
