<template>
  <div class="p-6 max-w-xl mx-auto">
    <h1 class="text-xl font-semibold">Регистрация мастера</h1>
    <div class="mt-4 space-y-4">
      <div>
        <label class="block text-sm font-medium">ФИО</label>
        <input v-model="form.name" type="text" class="mt-1 w-full border rounded p-2" />
      </div>
      <div>
        <label class="block text-sm font-medium">Город</label>
        <select v-model="form.city_id" class="mt-1 w-full border rounded p-2">
          <option :value="null">Выберите город</option>
          <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium">Услуги</label>
        <div class="mt-2 grid grid-cols-1 gap-2">
          <label v-for="s in services" :key="s.id" class="inline-flex items-center gap-2">
            <input type="checkbox" :value="s.id" v-model="form.services" />
            <span>{{ s.name }}</span>
          </label>
        </div>
      </div>
      <div class="text-red-600 text-sm" v-if="error">{{ error }}</div>
      <div class="text-green-700 text-sm" v-if="success">{{ success }}</div>
      <button class="bg-black text-white px-4 py-2 rounded" @click="submit" :disabled="loading">Зарегистрироваться</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({ cities: Array, services: Array })
const form = ref({ name: '', city_id: null, services: [] })
const error = ref('')
const success = ref('')
const loading = ref(false)
const initData = ref('')

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
  const payload = { ...form.value, initData: initData.value }
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
    if (ok) { window.Telegram.WebApp.ready(); window.Telegram.WebApp.expand(); initData.value = window.Telegram.WebApp.initData || '' }
  } catch (e) { initData.value = '' }
})
</script>

<style scoped>
</style>

