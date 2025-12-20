import './bootstrap'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import AppLayout from './Layouts/AppLayout.vue'
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import 'primeicons/primeicons.css'

// Register Service Worker
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .then((registration) => {
        console.log('SW registered: ', registration.scope)
      })
      .catch((registrationError) => {
        console.log('SW registration failed: ', registrationError)
      })
  })
}

const inertiaRoot = document.querySelector('[data-page]')

if (inertiaRoot) {
  createInertiaApp({
    resolve: (name) => {
      const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
      return pages[`./Pages/${name}.vue`]
    },
    setup({ el, App, props, plugin }) {
      // Удаляем лоадер, если он есть (для надежности)
      const loader = document.getElementById('app-loading')
      if (loader) loader.remove()
      
      // Инициализация Telegram WebApp
      if (window.Telegram?.WebApp) {
          window.Telegram.WebApp.ready()
          window.Telegram.WebApp.expand()
      }

      createApp({ render: () => h(App, props) })
        .use(plugin)
        .use(PrimeVue, {
          theme: {
            preset: Aura,
            options: {
                darkModeSelector: '.my-app-dark', // Отключаем автоматический dark mode по системе
            }
          }
        })
        .mount(el)
    },
  })
}