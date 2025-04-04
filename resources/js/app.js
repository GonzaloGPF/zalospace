import './vendor'
import '../css/app.css'

import '@/vendor'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m'
import globalConfig from '@/config/globalConfig.js'
import i18n, { options } from '@/plugins/i18n.js'
import { createPinia } from 'pinia'

createInertiaApp({
  title: (title) => {
    const appName = globalConfig.getAppName()

    if (title) {
      return `${title} - ${appName}`
    }

    return appName
  },
  resolve: (name) =>
    resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob('./Pages/**/*.vue')
    ),
  setup({ el, App, props, plugin }) {
    const pinia = createPinia()
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue, Ziggy)
      .use(i18n, options)
      .use(pinia)
      .mount(el)
  },
  progress: {
    color: '#4B5563',
  },
})
