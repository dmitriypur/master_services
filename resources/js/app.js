import './bootstrap'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import AppLayout from './Layouts/AppLayout.vue'
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import 'primeicons/primeicons.css'

// Helper to prime cache
const primeCache = async () => {
    try {
        // 1. Fetch current page (HTML) to force SW to cache it
        await fetch(window.location.href);
        
        // 2. Fetch all scripts and styles currently in DOM
        const assets = [
            ...Array.from(document.scripts).map(s => s.src).filter(src => src && !src.includes('chrome-extension') && !src.startsWith('blob:')),
            ...Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map(l => l.href)
        ];

        // Unique URLs
        const uniqueAssets = [...new Set(assets)];

        // Fetch them (SW will intercept and cache)
        uniqueAssets.forEach(url => fetch(url));
        
        console.log('Cache priming initiated for', uniqueAssets.length, 'assets');
    } catch (e) {
        console.error('Cache priming failed', e);
    }
};

// Register Service Worker
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .then((registration) => {
        console.log('SW registered: ', registration.scope);
        
        // Check if controller is active, if so, prime cache immediately
        if (navigator.serviceWorker.controller) {
            primeCache();
        } else {
            // Wait for controller change (first install)
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                primeCache();
            });
        }
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
