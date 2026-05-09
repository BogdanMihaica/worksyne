import './assets/main.css'
import 'primeicons/primeicons.css'

import { createApp } from 'vue'
import App from './App.vue'
import { registerHttp } from './plugins/http'
import { registerPrimeVue } from './plugins/primevue'

const app = createApp(App)

registerHttp(app)
registerPrimeVue(app)

app.mount('#app')
