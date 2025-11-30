<template>
  <div class="max-w-md mx-auto mt-10 p-6 bg-white border rounded">
    <h1 class="text-xl font-semibold mb-4">Вход</h1>
    <form method="POST" action="/login" class="space-y-4">
      <input type="hidden" name="_token" :value="csrf" />
      <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input name="email" type="email" required class="w-full border rounded px-3 py-2" :value="old.email || ''" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Пароль</label>
        <input name="password" type="password" required class="w-full border rounded px-3 py-2" />
      </div>
      <div class="flex items-center gap-2">
        <input id="remember" name="remember" type="checkbox" class="border rounded" />
        <label for="remember" class="text-sm">Запомнить меня</label>
      </div>
      <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
      <button type="submit" class="w-full bg-black text-white py-2 rounded">Войти</button>
    </form>

    <div class="mt-4 text-center text-sm">
      <a href="/master/register" class="text-blue-600 hover:underline">Регистрация для мастера</a>
    </div>

    <div class="mt-6">
      <div class="relative">
        <div class="absolute inset-0 flex items-center">
          <div class="w-full border-t border-gray-300"></div>
        </div>
        <div class="relative flex justify-center text-sm">
          <span class="px-2 bg-white text-gray-500">Или войти через Telegram</span>
        </div>
      </div>
      <div class="mt-6 flex justify-center" id="telegram-login-container"></div>
    </div>
  </div>
  
</template>

<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed, onMounted } from 'vue'

const page = usePage()
const csrf = computed(() => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '')
const old = computed(() => page.props?.ziggy?.old ?? page.props?.old ?? {})
const error = computed(() => {
  const e = page.props?.errors ?? {}
  return e.email || e.password || ''
})

const botName = computed(() => page.props?.telegramBotName || 'YOUR_BOT_USERNAME')

onMounted(() => {
  const script = document.createElement('script')
  script.src = 'https://telegram.org/js/telegram-widget.js?22'
  script.setAttribute('data-telegram-login', botName.value)
  script.setAttribute('data-size', 'large')
  script.setAttribute('data-onauth', 'onTelegramAuth(user)')
  script.setAttribute('data-request-access', 'write')
  document.getElementById('telegram-login-container').appendChild(script)

  window.onTelegramAuth = async (user) => {
    try {
      const res = await fetch('/auth/telegram/widget', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf.value,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(user)
      })
      const data = await res.json()
      if (data.redirect) {
        window.location.href = data.redirect
      }
    } catch (e) {
      console.error('Auth failed', e)
    }
  }
})
</script>

<style scoped>
</style>