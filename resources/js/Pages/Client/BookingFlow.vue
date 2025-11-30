<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <h1 class="text-2xl font-semibold">Запись к мастеру</h1>
    <div v-if="loadingMaster" class="text-gray-600">Загрузка мастера…</div>
    <div v-else class="text-sm text-gray-700">
      <div class="font-medium">{{ master?.name }}</div>
      <div v-if="master?.city" class="">{{ master.city.name }}</div>
      <div v-if="master?.address" class="">{{ master.address }}</div>
    </div>

    <div>
      <label class="block text-sm font-medium mb-2">Дата</label>
      <input type="date" v-model="date" class="block w-full rounded border px-3 py-2" />
    </div>

    <div>
      <label class="block text-sm font-medium mb-2">Услуга</label>
      <select v-model.number="serviceId" class="block w-full rounded border px-3 py-2">
        <option :value="null">Выберите услугу</option>
        <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
      </select>
    </div>

    <div>
      <h2 class="text-lg font-medium mb-2">Слоты</h2>
      <div v-if="loadingSlots" class="text-gray-600">Загрузка слотов…</div>
      <div v-else>
        <div v-if="slots.length === 0" class="text-gray-600">Нет слотов</div>
        <div v-else class="grid grid-cols-2 gap-3">
          <button
            v-for="s in slots"
            :key="s.starts_at"
            class="border rounded-lg p-3 flex flex-col gap-2"
            :disabled="!s.available"
            :class="s.available ? '' : 'opacity-50'"
            @click="selectSlot(s)"
          >
            <div class="font-mono text-sm">{{ s.time }}</div>
            <div :class="s.available ? 'text-green-600' : 'text-red-600'">{{ s.available ? 'свободен' : 'занят' }}</div>
          </button>
        </div>
      </div>
    </div>

    <div class="space-y-3" v-if="selectedSlot">
      <div class="text-sm text-gray-700">Вы выбрали: {{ date }} {{ selectedSlot.time }}</div>
      <input v-model="clientName" type="text" placeholder="Имя" class="block w-full rounded border px-3 py-2" />
      <input v-model="clientPhone" type="text" placeholder="Телефон (только цифры)" class="block w-full rounded border px-3 py-2" @input="onPhoneInput" />
      <div class="text-sm text-red-600" v-if="error">{{ error }}</div>
      <div class="flex items-center justify-end gap-2">
        <button class="inline-flex items-center rounded bg-gray-300 text-gray-900 px-3 py-1.5" @click="selectedSlot=null">Отмена</button>
        <button class="inline-flex items-center rounded bg-indigo-700 text-white px-3 py-1.5" :disabled="submitting" @click="submit">Подтвердить</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'

const props = defineProps({ id: { type: [String, Number], required: true } })
const masterId = ref(Number(props.id))
const master = ref(null)
const services = ref([])
const date = ref(new Date().toISOString().slice(0,10))
const slots = ref([])
const selectedSlot = ref(null)
const serviceId = ref(null)
const loadingMaster = ref(false)
const loadingSlots = ref(false)
const clientName = ref('')
const clientPhone = ref('')
const submitting = ref(false)
const error = ref('')

async function loadMaster() {
  loadingMaster.value = true
  try {
    const res = await fetch(`/api/masters/${masterId.value}`)
    const d = await res.json().catch(() => ({}))
    master.value = d?.data ?? d
    const sRes = await fetch(`/api/masters/${masterId.value}/services`)
    services.value = await sRes.json().then(x => x.data ?? x)
  } finally {
    loadingMaster.value = false
  }
}

async function loadSlots() {
  if (!date.value) return
  loadingSlots.value = true
  try {
    const res = await fetch(`/api/masters/${masterId.value}/slots?date=${encodeURIComponent(date.value)}`)
    slots.value = await res.json().then(d => Array.isArray(d) ? d : (d.data ?? []))
  } finally {
    loadingSlots.value = false
  }
}

function selectSlot(s) { selectedSlot.value = s }

function onPhoneInput(e) {
  const val = String(e.target.value || '')
  clientPhone.value = val.replace(/\D/g, '').slice(0, 11)
}

async function submit() {
  error.value = ''
  if (!selectedSlot.value || !serviceId.value) { error.value = 'Выберите услугу и слот'; return }
  const payload = {
    master_id: masterId.value,
    date: date.value,
    time: selectedSlot.value.time,
    service_id: serviceId.value,
    client_name: clientName.value,
    client_phone: clientPhone.value,
    preferred_channels: ['phone'],
    source: 'client',
  }
  submitting.value = true
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const res = await fetch('/api/appointments', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(payload),
      credentials: 'same-origin',
    })
    const d = await res.json().catch(() => ({}))
    if (!res.ok) { error.value = d.message || 'Ошибка создания'; return }
    selectedSlot.value = null
    await loadSlots()
  } finally {
    submitting.value = false
  }
}

onMounted(async () => { await loadMaster(); await loadSlots() })
watch(date, loadSlots)
</script>

<style scoped>
</style>
