<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full bg-white border rounded-xl shadow-sm p-6">
      <h1 class="text-xl font-semibold">Вход через Telegram WebApp</h1>
      <p class="text-sm text-gray-600 mt-2">Авторизация без перезагрузки.</p>
      <div class="mt-4 text-sm" :class="statusClass">{{ status }}</div>
      <div class="mt-6 flex items-center justify-between" v-if="!choiceVisible">
        <button class="inline-flex items-center rounded-lg bg-black text-white px-4 py-2" @click="tryAuth">Повторить</button>
      </div>
      <div class="mt-6 grid grid-cols-2 gap-3" v-else>
        <button class="inline-flex items-center justify-center rounded-lg bg-black text-white px-4 py-2" @click="goMaster">Я мастер</button>
        <button class="inline-flex items-center justify-center rounded-lg bg-gray-900 text-white px-4 py-2" @click="goClient">Я клиент</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'

const status = ref('Страница загружена')
const choiceVisible = ref(false)
const registerUrl = ref('/master/register')

const statusClass = computed(() => {
  if (status.value.startsWith('Ошибка')) return 'text-red-600'
  if (status.value.startsWith('Успешно')) return 'text-green-600'
  return 'text-gray-700'
})

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
  if (!ok) { status.value = 'Ошибка загрузки Telegram SDK'; sendStage('sdk-error'); return }

  try { window.Telegram?.WebApp?.ready(); window.Telegram?.WebApp?.expand?.() } catch (e) {}

  let initData = window?.Telegram?.WebApp?.initData || ''
  if (!initData) {
    const q = new URLSearchParams(window.location.search)
    const fromQuery = q.get('initData')
    if (fromQuery) { initData = fromQuery; status.value = 'initData (mock) ok'; sendStage('init-mock') }
  }
  if (!initData) { status.value = 'Нет initData'; sendStage('no-init'); return }
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
        // Здесь мы передаем initData через URL, чтобы форма регистрации сразу его подхватила
        // и не требовала пароль, а сразу регистрировала через Telegram
        // Важно: initData нужно закодировать
        window.location.href = '/master/register?initData=' + encodeURIComponent(initData)
        return
    }

    let msg = 'Ошибка авторизации'
    try {
      const data = await res.json()
      if (res.status === 403) {
        const url = data?.register_url || '/master/register'
        window.location.href = url
        return
      } else {
        msg = data.message || msg
      }
    } catch (e) {}
    status.value = msg
    sendStage('auth-error-' + res.status)
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

function goMaster() {
  window.location.href = registerUrl.value
}

function goClient() {
  window.location.href = '/book?webview=1'
}

onMounted(() => { status.value = 'Страница загружена'; sendStage('loaded'); tryAuth() })
</script>

<style scoped>
</style>
