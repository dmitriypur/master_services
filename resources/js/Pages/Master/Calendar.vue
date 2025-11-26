<template>
  <div class="max-w-3xl mx-auto py-8 px-4">
    <div class="mb-4 flex items-center gap-3">
      <Link href="/master/settings" class="inline-flex items-center rounded bg-gray-900 text-white px-3 py-1.5">Настройки</Link>
      <span class="text-gray-500 text-sm">Календарь</span>
    </div>
    <h1 class="text-2xl font-semibold mb-6">Календарь мастера</h1>

    <div class="mb-6">
      <label class="block text-sm font-medium mb-2">Дата</label>
      <VueDatePicker v-model="selectedDate" :enable-time="false" :teleport="true" :clearable="false" :format="'yyyy-MM-dd'" />
    </div>

    <div>
      <h2 class="text-lg font-medium mb-3">Слоты</h2>
      <div v-if="loading" class="text-gray-500">Загрузка…</div>
      <div v-else>
        <div v-if="slots.length === 0" class="text-gray-500">Нет слотов на выбранную дату</div>
        <ul v-else class="divide-y">
          <li v-for="s in slots" :key="s.starts_at" class="py-2 flex items-center justify-between cursor-pointer" @click="!s.available && openInfoModal(s)">
            <span class="font-mono">{{ s.time }}</span>
            <div class="flex items-center gap-3">
              <span :class="s.available ? 'text-green-600' : 'text-red-600'">{{ s.available ? 'свободен' : 'занят' }}</span>
              <button v-if="s.available" class="inline-flex items-center rounded bg-black text-white px-3 py-1" @click="openCreateModal(s)">Создать</button>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center">
      <div class="bg-white rounded-md shadow-lg w-full max-w-md p-4">
        <h3 class="text-lg font-semibold mb-4">Новая запись</h3>
        <form @submit.prevent="submitCreate" class="space-y-4">
          <div class="text-sm text-gray-700">Время: <span class="font-mono">{{ form.time }}</span> | Дата: <span class="font-mono">{{ form.date }}</span></div>
          <div>
            <label class="block text-sm font-medium mb-2">Услуга</label>
            <select v-model.number="form.service_id" class="block w-full rounded border px-3 py-2">
              <option :value="null">Выберите услугу</option>
              <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Клиент</label>
            <div class="flex items-center gap-4 mb-2 text-sm">
              <label class="flex items-center gap-2"><input type="radio" value="existing" v-model="clientMode"> Существующий</label>
              <label class="flex items-center gap-2"><input type="radio" value="new" v-model="clientMode"> Новый</label>
            </div>
            <div v-if="clientMode === 'existing'">
              <select v-model.number="form.client_id" class="block w-full rounded border px-3 py-2">
                <option :value="null">Выберите клиента</option>
                <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }} — {{ c.phone }}</option>
              </select>
            </div>
            <div v-else class="space-y-3">
              <input v-model="form.client_name" type="text" placeholder="Имя" class="block w-full rounded border px-3 py-2" />
              <input v-model="form.client_phone" type="text" placeholder="Телефон" class="block w-full rounded border px-3 py-2" />
              <div class="text-sm">
                <div class="mb-1">Предпочтительные каналы</div>
                <div class="flex items-center gap-3">
                  <label class="flex items-center gap-2"><input type="checkbox" value="phone" v-model="form.preferred_channels"> Телефон</label>
                  <label class="flex items-center gap-2"><input type="checkbox" value="telegram" v-model="form.preferred_channels"> Telegram</label>
                  <label class="flex items-center gap-2"><input type="checkbox" value="whatsapp" v-model="form.preferred_channels"> WhatsApp</label>
                </div>
              </div>
            </div>
          </div>

          <div v-if="errorMessage" class="text-red-600 text-sm">{{ errorMessage }}</div>

          <div class="flex items-center justify-end gap-3">
            <button type="button" class="rounded border px-3 py-2" @click="closeModal">Отмена</button>
            <button type="submit" class="rounded bg-black text-white px-4 py-2">Создать</button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showInfoModal" class="fixed inset-0 bg-black/40 flex items-center justify-center">
      <div class="bg-white rounded-md shadow-lg w-full max-w-md p-4">
        <h3 class="text-lg font-semibold mb-4">Запись</h3>
        <div class="space-y-3 text-sm">
          <div>Время: <span class="font-mono">{{ info.time }}</span> | Дата: <span class="font-mono">{{ info.date }}</span></div>
          <div>Клиент: <span class="font-medium">{{ info.client?.name }}</span> <span class="text-gray-600">{{ info.client?.phone }}</span></div>
          <div>Услуга: <span class="font-medium">{{ info.service?.name }}</span></div>
        </div>
        <div class="flex items-center justify-end gap-3 mt-4">
          <button type="button" class="rounded bg-black text-white px-3 py-2" @click="notifyClient">Напомнить клиенту</button>
          <button type="button" class="rounded border px-3 py-2" @click="closeInfo">Закрыть</button>
        </div>
      </div>
    </div>
  </div>
  
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { ref, watch, onMounted } from 'vue'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

const props = defineProps({ user: Object })

const selectedDate = ref(new Date())
const slots = ref([])
const loading = ref(false)
const services = ref([])
const clients = ref([])
const showModal = ref(false)
const errorMessage = ref('')
const clientMode = ref('existing')
const form = ref({ date: '', time: '', service_id: null, client_id: null, client_name: '', client_phone: '', preferred_channels: [] })
const showInfoModal = ref(false)
const info = ref({ id: null, date: '', time: '', client: null, service: null })

function formatDateLocal(date) {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

async function fetchSlots() {
  loading.value = true
  try {
    const dateStr = formatDateLocal(selectedDate.value)
    const res = await fetch(`/api/masters/${props.user.id}/slots?date=${encodeURIComponent(dateStr)}`)
    const json = await res.json()
    slots.value = Array.isArray(json) ? json : (json.data ?? [])
  } finally {
    loading.value = false
  }
}

onMounted(fetchSlots)
watch(selectedDate, fetchSlots)

async function fetchServicesAndClients() {
  const [sRes, cRes] = await Promise.all([
    fetch(`/api/masters/${props.user.id}/services`, { credentials: 'same-origin' }),
    fetch('/api/clients', { credentials: 'same-origin' }),
  ])
  services.value = (await sRes.json()).data ?? []
  clients.value = (await cRes.json()).data ?? []
}

function openCreateModal(slot) {
  const dateStr = formatDateLocal(selectedDate.value)
  form.value = { date: dateStr, time: slot.time, service_id: null, client_id: null, client_name: '', client_phone: '', preferred_channels: [] }
  clientMode.value = 'existing'
  errorMessage.value = ''
  showModal.value = true
  if (services.value.length === 0 || clients.value.length === 0) {
    fetchServicesAndClients()
  }
}

function closeModal() {
  showModal.value = false
}

async function submitCreate() {
  errorMessage.value = ''
  const payload = { date: form.value.date, time: form.value.time, service_id: form.value.service_id }
  if (clientMode.value === 'existing') {
    payload.client_id = form.value.client_id
  } else {
    payload.client_name = form.value.client_name
    payload.client_phone = form.value.client_phone
    payload.preferred_channels = form.value.preferred_channels
  }
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  const res = await fetch('/api/appointments', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf,
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify(payload),
    credentials: 'same-origin',
  })
  if (!res.ok) {
    let msg = 'Ошибка создания записи'
    try {
      const data = await res.json()
      msg = data.message || Object.values(data.errors || {})[0]?.[0] || msg
    } catch (e) {}
    errorMessage.value = msg
    return
  }
  closeModal()
  await fetchSlots()
}

async function openInfoModal(slot) {
  const dateStr = formatDateLocal(selectedDate.value)
  info.value = { id: null, date: dateStr, time: slot.time, client: null, service: null }
  const res = await fetch(`/api/appointments/at?date=${encodeURIComponent(dateStr)}&time=${encodeURIComponent(slot.time)}`, { credentials: 'same-origin' })
  if (res.ok) {
    const data = await res.json()
    const a = data.data ?? data
    info.value.id = a.id ?? null
    info.value.client = a.client ?? null
    info.value.service = a.service ?? null
    showInfoModal.value = true
  }
}

function closeInfo() {
  showInfoModal.value = false
}

async function notifyClient() {
  if (!info.value.id) return
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  const res = await fetch(`/api/appointments/${info.value.id}/notify`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf,
      'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'same-origin',
  })
  if (res.ok) {
    const data = await res.json()
    const url = data.whatsapp_url
    if (url) {
      try { window.open(url, '_blank') } catch (e) { window.location.href = url }
    }
  }
}
</script>

<style scoped>
</style>