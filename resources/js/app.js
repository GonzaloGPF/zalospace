import './vendor'
import '../css/app.css'

import '@/vendor'
import { createInertiaApp } from '@inertiajs/vue3'
import { ZiggyVue } from 'ziggy-js'
import globalConfig from '@/config/globalConfig.js'
import i18n, { options } from '@/plugins/i18n.js'
import { createPinia } from 'pinia'

createInertiaApp({
  pages: './Pages',
  title: (title) => {
    const appName = globalConfig.getAppName()

    if (title) {
      return `${title} - ${appName}`
    }

    return appName
  },
  withApp(app) {
    app.use(ZiggyVue, Ziggy).use(i18n, options).use(createPinia())
  },
  progress: {
    color: '#4B5563',
  },
})
