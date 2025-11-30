<template>
  <div class="max-w-3xl mx-auto py-4">
    <div class="mb-4 flex items-center gap-3">
      <Link href="/master/settings" class="inline-flex text-sm items-center rounded bg-gray-900 text-white px-3 py-1.5">Настройки</Link>
      <Link href="/master/clients" class="inline-flex text-sm items-center rounded bg-gray-900 text-white px-3 py-1.5">Клиенты</Link>
      <span class="text-gray-500 text-sm">Календарь</span>
    </div>

    <div class="mb-6">
      <VueDatePicker
        v-model="selectedDate"
         class="booking-picker"
        :enable-time="false"
        :time-config="{ enableTimePicker: false }"
        :inline="true"
        :hide-input="true"
        :auto-apply="true"
        :teleport="false"
        :clearable="false"
        :flow="['calendar']"
        :start-date="selectedDate"
        :locale="ruLocale"
        :week-start="1"
        :format="'yyyy-MM-dd'"
      />
    </div>

    <div>
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-medium">Слоты</h2>
        <div class="flex items-center gap-3">
          <div class="text-sm text-gray-600">Дата: <span class="font-mono">{{ formatDateLocal(selectedDate) }}</span></div>
          <button class="inline-flex items-center rounded bg-red-700 text-white px-3 py-1.5" @click="makeDayOff">Сделать выходным</button>
        </div>
      </div>
      <div v-if="loading" class="text-gray-500">Загрузка…</div>
      <div v-else>
        <div v-if="slots.length === 0" class="text-gray-500">Нет слотов на выбранную дату</div>
        <div v-else class="grid grid-cols-2 gap-3">
          <div
            v-for="s in slots"
            :key="s.starts_at"
            class="border border-gray-300 rounded-lg p-3 flex flex-col gap-2 cursor-pointer"
            @click="s.available ? openCreateModal(s) : openInfoModal(s)"
          >
            <div class="font-mono text-sm">{{ s.time }}</div>
            <div class="flex items-center justify-between">
              <span :class="s.available ? 'text-green-600' : 'text-red-600'">{{ s.available ? 'свободен' : 'занят' }}</span>
              <span class="ml-2 text-xs text-gray-500">{{ s.available ? 'создать' : 'посмотреть' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Modal :open="showModal" @close="closeModal">
      <template #title>
        <div class="flex items-center gap-3">
          <button type="button" :class="modalTab==='book' ? 'font-semibold' : 'text-gray-500'" @click="modalTab='book'">Записать Клиента</button>
          <span class="text-gray-400">•</span>
          <button type="button" :class="modalTab==='break' ? 'font-semibold' : 'text-gray-500'" @click="modalTab='break'">Установить Перерыв</button>
        </div>
      </template>
      <form v-if="modalTab==='book'" @submit.prevent="submitCreate" class="space-y-4">
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
              <button type="button" class="inline-flex items-center rounded bg-gray-900 text-white px-2 py-1" @click="voiceOpen = !voiceOpen">Голосовой Ввод</button>
            </div>
            <div v-if="clientMode === 'existing'">
              <select v-model.number="form.client_id" class="block w-full rounded border px-3 py-2">
                <option :value="null">Выберите клиента</option>
                <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }} — {{ c.phone }}</option>
              </select>
            </div>
            <div v-else class="space-y-3">
              <input v-model="form.client_name" type="text" placeholder="Имя" class="block w-full rounded border px-3 py-2" />
              <input v-model="form.client_phone" type="text" inputmode="numeric" maxlength="11" placeholder="Телефон (только цифры)" class="block w-full rounded border px-3 py-2" @input="onPhoneInput" />
              <div class="text-sm">
                <div class="mb-1">Предпочтительные каналы</div>
                <div class="flex items-center gap-3">
                  <label class="flex items-center gap-2"><input type="checkbox" value="phone" v-model="form.preferred_channels"> Телефон</label>
                  <label class="flex items-center gap-2"><input type="checkbox" value="telegram" v-model="form.preferred_channels"> Telegram</label>
                  <label class="flex items-center gap-2"><input type="checkbox" value="whatsapp" v-model="form.preferred_channels"> WhatsApp</label>
                </div>
              </div>
              <div v-if="clientMode==='new' && !phoneValid" class="text-red-600 text-sm">Телефон: только цифры, 5–11 символов</div>
              <div v-if="voiceOpen" class="mt-3 space-y-2">
                <textarea v-model="voiceText" rows="3" class="block w-full rounded border px-3 py-2" placeholder="Продиктуйте или вставьте текст: например, 'Светлана, завтра в 14:30 маникюр, телефон 89991234567'" />
                <div class="flex items-center gap-2">
                  <Button class="bg-indigo-700" type="button" @click="parseVoice">Распознать</Button>
                  <div v-if="voiceError" class="text-red-600 text-sm">{{ voiceError }}</div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="errorMessage" class="text-red-600 text-sm">{{ errorMessage }}</div>

          <div class="flex items-center justify-end gap-3">
            <Button class="bg-red-700" type="button" @click="closeModal">Отмена</Button>
            <Button class="bg-indigo-700" type="submit">Создать</Button>
          </div>
      </form>
      <div v-else class="space-y-4">
        <div class="text-sm text-gray-700">Время: <span class="font-mono">{{ form.time }}</span> | Дата: <span class="font-mono">{{ form.date }}</span></div>
        <div>
          <label class="block text-sm font-medium mb-2">Длительность перерыва</label>
          <select v-model.number="breakDurationMin" class="block w-full rounded border px-3 py-2">
            <option :value="15">15 мин</option>
            <option :value="30">30 мин</option>
            <option :value="45">45 мин</option>
            <option :value="60">60 мин</option>
          </select>
        </div>
        <div class="flex items-center justify-end gap-3">
          <Button class="bg-red-700" type="button" @click="closeModal">Отмена</Button>
          <Button class="bg-amber-600" type="button" @click="submitBreak">Установить</Button>
        </div>
      </div>
    </Modal>

    <Modal :open="showInfoModal" @close="closeInfo">
      <template #title>Запись</template>
        <div class="space-y-3 text-sm">
          <div>Время: <span class="font-mono">{{ info.time }}</span> | Дата: <span class="font-mono">{{ info.date }}</span></div>
          <a :href="'tel:' + info.client?.phone" class="block">Клиент: <span class="font-medium">{{ info.client?.name }}</span> <span class="text-gray-600">{{ info.client?.phone }}</span></a>
          <div>Услуга: <span class="font-medium">{{ info.service?.name }}</span></div>
        </div>
        <div class="flex flex-wrap gap-2 items-center justify-between mt-4">
          <Button class="bg-red-700" type="button" @click="closeInfo">Закрыть</Button>
          <Button class="bg-green-700" type="button" @click="notifyClient">Напомнить клиенту</Button>
          <Button class="bg-indigo-600" type="button" @click="cancelAppointment">Удалить</Button>
        </div>
    </Modal>
  </div>
  
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { ref, watch, onMounted, computed } from 'vue'
import { ru as ruLocale } from 'date-fns/locale'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import Modal from '../../components/UI/Modal.vue'
import Button from '../../components/UI/Button.vue'

const props = defineProps({ user: Object })

const selectedDate = ref(new Date())
const slots = ref([])
const loading = ref(false)
const services = ref([])
const clients = ref([])
const showModal = ref(false)
const errorMessage = ref('')
const clientMode = ref('existing')
const modalTab = ref('book')
const form = ref({ date: '', time: '', service_id: null, client_id: null, client_name: '', client_phone: '', preferred_channels: [] })
const showInfoModal = ref(false)
const info = ref({ id: null, date: '', time: '', client: null, service: null })
const MIN_PHONE_DIGITS = 5
const MAX_PHONE_DIGITS = 11
const breakDurationMin = ref(30)
const voiceOpen = ref(false)
const voiceText = ref('')
const voiceError = ref('')
const phoneValid = computed(() => {
  const len = (form.value.client_phone || '').length
  return clientMode.value === 'existing' ? true : (len >= MIN_PHONE_DIGITS && len <= MAX_PHONE_DIGITS)
})
function onPhoneInput(e) {
  const val = String(e.target.value || '')
  const digits = val.replace(/\D/g, '').slice(0, MAX_PHONE_DIGITS)
  form.value.client_phone = digits
}

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
  modalTab.value = 'book'
  voiceOpen.value = false
  voiceText.value = ''
  voiceError.value = ''
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
    if (!phoneValid.value) { errorMessage.value = 'Телефон: только цифры, 5–11'; return }
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

function addMinutesToTime(timeStr, minutes) {
  const [hh, mm] = String(timeStr || '00:00').split(':').map((v) => parseInt(v, 10) || 0)
  let total = hh * 60 + mm + (minutes || 0)
  if (total < 0) total = 0
  const endH = Math.min(23, Math.floor(total / 60))
  const endM = total % 60
  return String(endH).padStart(2, '0') + ':' + String(endM).padStart(2, '0')
}

async function submitBreak() {
  const dateStr = form.value.date
  const timeFrom = `${dateStr} ${form.value.time}`
  const timeTo = `${dateStr} ${addMinutesToTime(form.value.time, breakDurationMin.value)}`
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  const res = await fetch('/api/master/schedule-exceptions', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ type: 'break', time_from: timeFrom, time_to: timeTo }),
    credentials: 'same-origin',
  })
  if (!res.ok) {
    try { const d = await res.json(); errorMessage.value = d.message || 'Ошибка установки перерыва' } catch (e) { errorMessage.value = 'Ошибка установки перерыва' }
    return
  }
  closeModal()
  await fetchSlots()
}

async function makeDayOff() {
  const dateStr = formatDateLocal(selectedDate.value)
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  const res = await fetch('/api/master/schedule-exceptions', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ type: 'day_off', date: dateStr }),
    credentials: 'same-origin',
  })
  if (!res.ok) {
    try { const d = await res.json(); /* swallow error UI */ } catch (e) {}
  }
  await fetchSlots()
}

async function parseVoice() {
  voiceError.value = ''
  const text = voiceText.value.trim()
  if (!text) { voiceError.value = 'Введите или продиктуйте текст'; return }
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  const res = await fetch('/api/master/parse-voice-command', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ text }),
    credentials: 'same-origin',
  })
  if (!res.ok) {
    try { const d = await res.json(); voiceError.value = d.message || 'Ошибка распознавания' } catch (e) { voiceError.value = 'Ошибка распознавания' }
    return
  }
  const data = await res.json().catch(() => ({}))
  const r = data || {}
  if (r.client_name) { form.value.client_name = String(r.client_name) }
  if (r.phone) { form.value.client_phone = String(r.phone).replace(/\D+/g, '').slice(0, MAX_PHONE_DIGITS) }
  if (r.time) {
    const t = String(r.time)
    const m = t.match(/(\d{1,2}:\d{2})/)
    if (m) { form.value.time = m[1] }
  }
  if (r.service_name && Array.isArray(services.value)) {
    const name = String(r.service_name).toLowerCase().trim()
    const found = services.value.find((s) => String(s.name || '').toLowerCase().includes(name))
    if (found) { form.value.service_id = found.id }
  }
  clientMode.value = 'new'
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

async function cancelAppointment() {
  if (!info.value.id) return
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  const res = await fetch(`/api/appointments/${info.value.id}/cancel`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf,
      'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'same-origin',
  })
  if (res.ok) {
    closeInfo()
    await fetchSlots()
  }
}
</script>

<style scoped>
:deep(.booking-picker) {
  width: 100%;
  flex-direction: column;

    div{
        width: 100%;
    }

  .dp__theme_light{
    --dp-background-color: #ffffff;
    --dp-text-color: #1f2937;
    --dp-primary-color: #f59e0b !important;
    --dp-hover-color: #fde68a;
    --dp-highlight-color: #f97316;
    --dp-active-text-color: #0f172a;
    --dp-border-radius: 12px;
    --dp-input-padding: 12px;
  }
  
}
</style>
