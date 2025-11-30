<template>
  <div class="p-6 max-w-xl mx-auto">
    <h1 class="text-xl font-semibold">Регистрация мастера</h1>
    <div class="mt-4 space-y-4">
      <!-- Telegram Widget для регистрации -->
      <div v-if="!initData && !telegramUser" class="mb-4">
        <p class="text-sm font-medium mb-2">Для быстрой регистрации войдите через Telegram:</p>
        <div id="telegram-register-container"></div>
        <div class="relative my-4">
          <div class="absolute inset-0 flex items-center">
             <div class="w-full border-t border-gray-300"></div>
          </div>
          <div class="relative flex justify-center text-sm">
             <span class="px-2 bg-white text-gray-500">Или заполните форму вручную</span>
          </div>
        </div>
      </div>

      <div v-if="telegramUser" class="bg-blue-50 p-3 rounded text-sm text-blue-800 mb-4 flex items-center gap-2">
        <span>Ваш Telegram: <strong>{{ telegramUser.first_name }} {{ telegramUser.last_name }}</strong></span>
        <button @click="telegramUser = null" class="text-xs underline text-blue-600">Отменить</button>
      </div>

      <div>
        <label class="block text-sm font-medium">ФИО</label>
        <input v-model="form.name" type="text" class="mt-1 w-full border rounded p-2" />
      </div>
      <div v-if="!initData && !telegramUser">
        <div>
          <label class="block text-sm font-medium">Город</label>
          <select v-model="form.city_id" class="mt-1 w-full border rounded p-2">
            <option :value="null">Выберите город</option>
            <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div class="mt-4">
          <label class="block text-sm font-medium">Телефон</label>
          <input v-model="form.phone" type="text" inputmode="numeric" maxlength="11" class="mt-1 w-full border rounded p-2" placeholder="Телефон (только цифры)" />
        </div>
        <div class="mt-4">
          <label class="block text-sm font-medium">Пароль</label>
          <input v-model="form.password" type="password" class="mt-1 w-full border rounded p-2" placeholder="Минимум 8 символов" />
        </div>
        <div class="mt-4">
          <label class="block text-sm font-medium">Услуги</label>
          <div class="mt-2 grid grid-cols-1 gap-2">
            <label v-for="s in services" :key="s.id" class="inline-flex items-center gap-2">
              <input type="checkbox" :value="s.id" v-model="form.services" />
              <span>{{ s.name }}</span>
            </label>
          </div>
        </div>
      </div>
      
      <div v-else>
         <p class="text-gray-600 text-sm mb-4">Нажимая "Зарегистрироваться", вы принимаете условия сервиса. Остальные данные можно заполнить позже в личном кабинете.</p>
      </div>
      <div class="text-red-600 text-sm" v-if="error">{{ error }}</div>
      <div class="text-green-700 text-sm" v-if="success">{{ success }}</div>
      <button class="bg-black text-white px-4 py-2 rounded" @click="submit" :disabled="loading">Зарегистрироваться</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const props = defineProps({ cities: Array, services: Array })
const form = ref({ name: '', city_id: null, services: [], phone: '', password: '' })
const error = ref('')
const success = ref('')
const loading = ref(false)
const initData = ref('')
const telegramUser = ref(null)

const botName = computed(() => page.props?.telegramBotName || 'YOUR_BOT_USERNAME')

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

async function submit() {
  error.value = ''
  success.value = ''
  loading.value = true
  const payload = { 
    ...form.value, 
    initData: initData.value,
    telegram_user: telegramUser.value 
  }
  try {
    const res = await fetch('/auth/telegram/master/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      credentials: 'same-origin',
    })
    const data = await res.json().catch(() => ({}))
    if (!res.ok) { error.value = data.message || 'Ошибка регистрации'; loading.value = false; return }
    success.value = 'Успешно'
    const dest = (data && data.redirect) ? data.redirect : '/master/settings'
    window.location.href = dest
  } catch (e) {
    error.value = 'Ошибка регистрации'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  const ok = await injectTelegram()
  try {
    if (ok) {
      window.Telegram.WebApp.ready();
      window.Telegram.WebApp.expand();
      initData.value = window.Telegram.WebApp.initData || ''
      const u = window.Telegram.WebApp.initDataUnsafe?.user || null
      if (u) {
        const fn = (u.first_name || '').trim()
        const ln = (u.last_name || '').trim()
        const un = (u.username || '').trim()
        form.value.name = [fn, ln].filter(Boolean).join(' ') || (un ? `@${un}` : '')
      }
    }
  } catch (e) { initData.value = '' }

  if (!initData.value) {
    // Если не в WebApp, инициализируем виджет для веба
    const script = document.createElement('script')
    script.src = 'https://telegram.org/js/telegram-widget.js?22'
    script.setAttribute('data-telegram-login', botName.value)
    script.setAttribute('data-size', 'large')
    script.setAttribute('data-onauth', 'onTelegramRegister(user)')
    script.setAttribute('data-request-access', 'write')
    const container = document.getElementById('telegram-register-container')
    if (container) container.appendChild(script)

    window.onTelegramRegister = (user) => {
      telegramUser.value = user
      // Автозаполнение имени
      const fn = (user.first_name || '').trim()
      const ln = (user.last_name || '').trim()
      const un = (user.username || '').trim()
      if (!form.value.name) {
        form.value.name = [fn, ln].filter(Boolean).join(' ') || (un ? `@${un}` : '')
      }
      // Автоматическая регистрация при входе через виджет
      submit()
    }
  }
})
</script>

<style scoped>
</style>
