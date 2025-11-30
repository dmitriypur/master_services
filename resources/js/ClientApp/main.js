import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import AppLayout from './AppLayout.vue'
import routes from './router'

const router = createRouter({ history: createWebHistory(), routes })

createApp(AppLayout).use(router).mount('#client-app')
