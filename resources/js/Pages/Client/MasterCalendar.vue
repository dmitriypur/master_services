<template>
  <div class="max-w-3xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-semibold mb-2">Запись к мастеру</h1>
    <div class="text-sm text-gray-700 mb-6">
      <div class="font-medium">{{ masterData.name }}</div>
      <div v-if="masterData.city" class="">{{ masterData.city.name }}</div>
      <div v-if="masterData.address" class="">{{ masterData.address }}</div>
    </div>

    <div class="mb-6">
      <label class="block text-sm font-medium mb-2">Дата</label>
      <VueDatePicker v-model="selectedDate" :enable-time="false" :teleport="true" :clearable="false" :format="'yyyy-MM-dd'" />
    </div>

    <div class="mb-6">
      <label class="block text-sm font-medium mb-2">Услуга</label>
      <select v-model.number="selectedServiceId" class="block w-full rounded border px-3 py-2">
        <option :value="null">Выберите услугу</option>
        <option v-for="s in servicesDisplay" :key="s.id" :value="s.id">{{ s.name }}</option>
      </select>
    </div>

    <div>
      <h2 class="text-lg font-medium mb-3">Слоты</h2>
      <div v-if="loading" class="text-gray-500">Загрузка…</div>
      <div v-else>
        <div v-if="slots.length === 0" class="text-gray-500">Нет слотов на выбранную дату</div>
        <ul v-else class="divide-y">
          <li v-for="s in slots" :key="s.starts_at" class="py-2 flex items-center justify-between">
            <span class="font-mono">{{ s.time }}</span>
            <div class="flex items-center gap-3">
              <span :class="s.available ? 'text-green-600' : 'text-red-600'">{{ s.available ? 'свободен' : 'занят' }}</span>
              <button class="inline-flex items-center rounded px-3 py-1"
                      :class="s.available ? 'bg-black text-white' : 'bg-gray-200 text-gray-500 cursor-not-allowed'"
                      :disabled="!s.available || !selectedServiceId"
                      @click="s.available && openCreateModal(s)">
                Записаться
              </button>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center">
      <div class="bg-white rounded-md shadow-lg w-full max-w-md p-4">
        <h3 class="text-lg font-semibold mb-4">Запись</h3>
        <form @submit.prevent="submitCreate" class="space-y-4">
          <div class="text-sm text-gray-700">Время: <span class="font-mono">{{ form.time }}</span> | Дата: <span class="font-mono">{{ form.date }}</span></div>

          <div>
            <input v-model="form.client_name" type="text" placeholder="Имя" class="block w-full rounded border px-3 py-2" />
          </div>
          <div>
            <input v-model="form.client_phone" type="text" placeholder="Телефон" class="block w-full rounded border px-3 py-2" />
          </div>
          <div class="text-sm">
            <div class="mb-1">Предпочтительные каналы</div>
            <div class="flex items-center gap-3">
              <label class="flex items-center gap-2"><input type="checkbox" value="phone" v-model="form.preferred_channels"> Телефон</label>
              <label class="flex items-center gap-2"><input type="checkbox" value="telegram" v-model="form.preferred_channels"> Telegram</label>
              <label class="flex items-center gap-2"><input type="checkbox" value="whatsapp" v-model="form.preferred_channels"> WhatsApp</label>
            </div>
          </div>

          <div v-if="errorMessage" class="text-red-600 text-sm">{{ errorMessage }}</div>

          <div class="flex items-center justify-end gap-3">
            <button type="button" class="rounded border px-3 py-2" @click="closeModal">Отмена</button>
            <button type="submit" class="rounded bg-black text-white px-4 py-2">Записаться</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

const props = defineProps({ master: Object })

const masterData = computed(() => (props.master && props.master.data) ? props.master.data : props.master)

const selectedDate = ref(new Date())
const selectedServiceId = ref(null)
const slots = ref([])
const loading = ref(false)
const showModal = ref(false)
const errorMessage = ref('')
const form = ref({ date: '', time: '', service_id: null, client_name: '', client_phone: '', preferred_channels: [] })

const servicesComputed = computed(() => {
  const raw = masterData.value?.services
  if (Array.isArray(raw)) return raw
  if (raw && Array.isArray(raw.data)) return raw.data
  return []
})

const servicesOverride = ref([])
const servicesDisplay = computed(() => servicesOverride.value.length ? servicesOverride.value : servicesComputed.value)

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
    const res = await fetch(`/api/masters/${masterData.value.id}/slots?date=${encodeURIComponent(dateStr)}`, { credentials: 'same-origin' })
    const text = await res.text()
    let json
    try { json = JSON.parse(text) } catch (e) { json = [] }
    slots.value = Array.isArray(json) ? json : (json.data ?? [])
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  const first = servicesComputed.value[0]?.id ?? null
  selectedServiceId.value = first
  fetchSlots()
  if (!first) {
    fetch(`/api/masters/${masterData.value.id}/services`, { credentials: 'same-origin' })
      .then(r => r.json())
      .then(j => { servicesOverride.value = j.data ?? []; if (!selectedServiceId.value && servicesOverride.value[0]) selectedServiceId.value = servicesOverride.value[0].id })
      .catch(() => {})
  }
})
watch(selectedDate, fetchSlots)

function openCreateModal(slot) {
  const dateStr = formatDateLocal(selectedDate.value)
  form.value = { date: dateStr, time: slot.time, service_id: selectedServiceId.value, client_name: '', client_phone: '', preferred_channels: [] }
  errorMessage.value = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

async function submitCreate() {
  errorMessage.value = ''
  const payload = {
    master_id: masterData.value.id,
    date: form.value.date,
    time: form.value.time,
    service_id: form.value.service_id,
    client_name: form.value.client_name,
    client_phone: form.value.client_phone,
    preferred_channels: form.value.preferred_channels,
    source: 'client',
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
</script>

<style scoped>
</style>