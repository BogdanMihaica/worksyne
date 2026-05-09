import Aura from '@primeuix/themes/aura'
import PrimeVue from 'primevue/config'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Message from 'primevue/message'
import Tag from 'primevue/tag'

export function registerPrimeVue(app) {
  app.use(PrimeVue, {
    theme: {
      preset: Aura,
      options: {
        darkModeSelector: false,
      },
    },
  })

  app.component('Button', Button)
  app.component('Column', Column)
  app.component('DataTable', DataTable)
  app.component('Message', Message)
  app.component('Tag', Tag)
}
