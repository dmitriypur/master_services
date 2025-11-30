<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-gray-900 mx-auto mb-4"></div>
      <p class="text-sm text-gray-600">Загрузка...</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'

const status = ref('Загрузка...')

function injectTelegram() {
  return new Promise((resolve) => {
    if (window.Telegram && window.Telegram.WebApp) return resolve(true)
    const s = document.createElement('script')
    s.src = 'https://telegram.org/js/telegram-web-app.js'
    s.onload = () => resolve(true)
    s.onerror = () => resolve(false)
    document.head.appendChild(s)
  })
}

function sendStage(stage) {
  try { navigator.sendBeacon('/debug/webapp-event', new Blob([JSON.stringify({ stage })], { type: 'application/json' })) } catch (e) {}
}

async function tryAuth() {
  sendStage('try-auth')
  const ok = await injectTelegram()
  if (!ok) { 
      // Если не удалось загрузить SDK, пробуем просто редиректнуть
      window.location.href = '/master/calendar'
      return 
  }

  try { window.Telegram?.WebApp?.ready(); window.Telegram?.WebApp?.expand?.() } catch (e) {}

  let initData = window?.Telegram?.WebApp?.initData || ''
  if (!initData) {
    const q = new URLSearchParams(window.location.search)
    const fromQuery = q.get('initData')
    if (fromQuery) { initData = fromQuery; status.value = 'initData (mock) ok'; sendStage('init-mock') }
  }
  if (!initData) { 
      // Нет данных initData - возможно открыто в браузере
      window.location.href = '/login'
      return 
  }
  status.value = `initData ok (${initData.length})`
  sendStage('init-ok')

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  const res = await fetch('/auth/telegram/webapp', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ initData }),
    credentials: 'same-origin',
  })
  if (!res.ok) {
    // Если пользователь не найден (404) или другая ошибка, но это WebApp - перенаправляем на регистрацию
    if (res.status === 404 || res.status === 401) {
        window.location.href = '/master/register?initData=' + encodeURIComponent(initData)
        return
    }

    // В случае любой другой ошибки пробуем редирект на логин
    window.location.href = '/login'
    return
  }
  const data = await res.json().catch(() => ({}))
  status.value = 'Успешно'
  sendStage('auth-ok')
  try {
    const t = data?.token || ''
    if (t) { localStorage.setItem('auth_token', t) }
  } catch (e) {}
  const dest = (data && data.redirect) ? data.redirect : '/master/calendar'
  window.location.href = dest
}

onMounted(() => {
  tryAuth()
})
</script>

<style scoped>
</style>
