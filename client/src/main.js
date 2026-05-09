import './assets/main.css'
import 'primeicons/primeicons.css'

import { createApp } from 'vue'
import App from './App.vue'
import { registerComponents } from './plugins/components'
import { registerHttp } from './plugins/http'
import { registerPrimeVue } from './plugins/primevue'
import { registerRouter } from './plugins/router'

const app = createApp(App)

registerHttp(app)
registerPrimeVue(app)
registerComponents(app)
registerRouter(app)

app.mount('#app')
