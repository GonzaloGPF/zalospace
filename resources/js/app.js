import './vendor'
import '../css/app.css'

import '@/vendor'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { ZiggyVue } from 'ziggy-js'
import globalConfig from '@/config/globalConfig.js'
import i18n, { options } from '@/plugins/i18n.js'
import { createPinia } from 'pinia'
import Translator from '@/objects/Translator.js'

// Inertia navigations don't reload the document, so the active translations
// and <html lang> would otherwise keep whatever locale was loaded at boot.
// Every page carries the locale the server resolved for its route (see
// SetLocale middleware), so we resync the client to it on every navigation.
router.on('navigate', (event) => Translator.syncLocale(event.detail.page.props.locale))

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
