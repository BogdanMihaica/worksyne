import './assets/main.css'
import 'primeicons/primeicons.css'

import { createApp } from 'vue'
import App from './App.vue'
import { registerComponents } from './plugins/components'
import { registerFontAwesome } from './plugins/fontawesome'
import { registerHttp } from './plugins/http'
import { registerPrimeVue } from './plugins/primevue'
import { registerRouter } from './plugins/router'
import { registerStrings } from './plugins/strings'
import { registerToast } from './plugins/toast'

const app = createApp(App)

registerHttp(app)
registerFontAwesome()
registerPrimeVue(app)
registerStrings(app)
registerToast(app)
registerComponents(app)
registerRouter(app)

app.mount('#app')
