<template>
  <div class="min-h-screen bg-white">
    <div class="p-6">
      <div class="text-xl font-semibold">Календарь мастера</div>
      <div class="text-sm text-gray-600 mt-1">{{ user?.name || 'Мастер' }}</div>
      <button class="mt-4 inline-flex items-center rounded-lg bg-black text-white px-4 py-2" @click="openFull">Открыть полную версию</button>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'

const props = defineProps({ user: Object })

function openFull() {
  window.location.href = '/master/calendar'
}

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

onMounted(async () => {
  const ok = await injectTelegram()
  if (ok) { try { window.Telegram.WebApp.ready(); window.Telegram.WebApp.expand() } catch (e) {} }
  sendStage('calendar-loaded')
})
</script>

<style scoped>
</style>
