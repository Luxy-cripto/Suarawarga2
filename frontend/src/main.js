import './assets/main.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import api from './services/api'

const app = createApp(App)

async function loadSiteName() {
  try {
    const response = await api.get('/settings')

    const siteName =
      response.data.site_name || 'SUARAWARGA'

    document.title = siteName
  } catch (error) {
    console.error('Gagal mengambil nama situs:', error)

    document.title = 'SUARAWARGA'
  }
}

loadSiteName()

app.use(router)
app.mount('#app')