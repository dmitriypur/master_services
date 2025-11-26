<template>
  <div class="max-w-md mx-auto mt-10 p-6 bg-white border rounded">
    <h1 class="text-xl font-semibold mb-4">Вход через Telegram WebApp</h1>
    <div v-if="error" class="text-red-600 text-sm mb-4">{{ error }}</div>
    <div v-if="loading" class="text-gray-600">Авторизация...</div>
    <div v-else class="text-gray-700">Инициализация...</div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { onMounted, ref } from 'vue'

const loading = ref(true)
const error = ref('')

onMounted(async () => {
  if (!window?.Telegram?.WebApp) {
    await new Promise((resolve) => {
      const s = document.createElement('script')
      s.src = 'https://telegram.org/js/telegram-web-app.js'
      s.onload = resolve
      document.head.appendChild(s)
    })
  }
  try {
    const initData = window?.Telegram?.WebApp?.initData || ''
    if (!initData) {
      error.value = 'Нет данных WebApp'
      loading.value = false
      return
    }
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const res = await fetch('/api/auth/telegram/webapp', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ initData }),
      credentials: 'same-origin',
    })
    if (!res.ok) {
      const data = await res.json().catch(() => ({}))
      error.value = data.message || 'Ошибка авторизации'
      loading.value = false
      return
    }
    const data = await res.json()
    if (data?.token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`
    }
    window.location.href = '/master/calendar'
  } catch (e) {
    error.value = 'Ошибка'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
</style>