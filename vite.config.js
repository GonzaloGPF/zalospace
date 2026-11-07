import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import inertia from '@inertiajs/vite'
import i18n from 'laravel-vue-i18n/vite'
import path from 'path'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  server: {
    // host: 'localhost',
    hmr: {
      host: 'localhost',
    },
  },
  plugins: [
    tailwindcss(),
    laravel({
      input: 'resources/js/app.js',
      refresh: true,
    }),
    inertia({
      ssr: false,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    i18n(),
  ],
  resolve: {
    alias: {
      '@': path.resolve(import.meta.dirname, 'resources/js'),
    },
  },
})
