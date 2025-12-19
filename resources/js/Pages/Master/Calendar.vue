<template>
  <div class="max-w-2xl mx-auto px-4 py-6 space-y-4 pb-20">
    <!-- Offline / Sync Status -->
    <Message v-if="!isOnline" severity="warn" icon="pi pi-exclamation-triangle" :closable="false">
       Отсутствует интернет. Вы можете создавать записи, они сохранятся локально.
    </Message>
    <Message v-if="isOnline && appointmentQueue.length > 0" severity="info" icon="pi pi-sync" :closable="false">
       Синхронизация {{ appointmentQueue.length }} записей...
    </Message>

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
    
    <Button 
        rounded 
        hidden
        icon="pi pi-microphone" 
        severity="help" 
        class="fixed bottom-20 left-4 shadow-lg z-50 !w-12 !h-12" 
        @click="openGlobalVoiceModal" 
    />

    <Card class="mb-20">
        <template #content>
            <div class="flex justify-between items-center mb-4">
                <div class="text-lg font-medium text-gray-700">
                    <span class="font-bold">{{ formatDateLocal(selectedDate) }}</span>
                </div>
                <Button 
                    v-if="!isDayOff" 
                    label="Сделать выходным" 
                    severity="danger" 
                    size="small" 
                    outlined 
                    @click="makeDayOff" 
                />
                <Button 
                    v-else 
                    label="Сделать рабочим" 
                    severity="success" 
                    size="small" 
                    outlined 
                    @click="cancelDayOff" 
                />
            </div>

            <div v-if="loading" class="flex justify-center py-8">
                <i class="pi pi-spin pi-spinner text-4xl text-gray-400"></i>
            </div>
            
            <div v-else>
                <!-- Debug info -->
                <div class="text-xs text-red-500 mb-2 p-2 bg-red-50 rounded">
                    Debug: User ID: {{ user?.id }}, Active: {{ user?.is_active }}, Slots: {{ slots.length }}<br>
                    Error: {{ fetchError }}
                </div>
                <div v-if="isDayOff" class="text-center py-8 text-gray-500">
                    <i class="pi pi-calendar-times text-4xl mb-2 block"></i>
                    Выходной день
                </div>
                <div v-else-if="slots.length === 0" class="text-center py-8 text-gray-500">
                    Нет слотов на выбранную дату. <br>
                    <small>Проверьте настройки графика.</small>
                </div>
                <div v-else class="grid grid-cols-2 gap-3">
                    <div
                        v-for="s in slots"
                        :key="s.starts_at"
                        class="border rounded-lg p-3 flex flex-col gap-1 cursor-pointer transition-all hover:shadow-md"
                        :class="[
                            s.is_past && s.available ? 'opacity-60 bg-gray-50 cursor-default' : 'bg-white border-gray-200',
                            !s.available && !s.is_past ? 'border-l-4 border-l-red-500' : '',
                            s.available && !s.is_past ? 'border-l-4 border-l-green-500' : ''
                        ]"
                        @click="handleClick(s)"
                    >
                        <div class="font-bold text-lg text-gray-800">{{ s.time }}</div>
                        <div class="flex items-center justify-between text-sm">
                            <!-- Прошедшие слоты -->
                            <template v-if="s.is_past">
                                <span v-if="s.available" class="text-gray-400">Прошел</span>
                                <span v-else class="text-red-600 font-medium">Занят</span>
                            </template>
                            <!-- Будущие слоты -->
                            <template v-else>
                                <span :class="s.available ? 'text-green-600' : 'text-red-600 font-medium'">
                                    {{ s.available ? 'Свободен' : 'Занят' }}
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Card>

    <!-- Плавающая панель навигации внизу -->
    <div class="fixed bottom-4 left-0 right-0 flex justify-center gap-4 px-4 z-10">
       <Link href="/master/settings">
         <Button label="Настройки" icon="pi pi-cog" severity="success" raised rounded />
       </Link>
       <Link href="/master/clients">
         <Button label="Клиенты" icon="pi pi-users" severity="info" raised rounded />
       </Link>
    </div>

    <Dialog v-model:visible="showModal" modal header="Запись" :style="{ width: '90vw', maxWidth: '500px' }" @hide="closeModal">
      <Tabs value="book">
        <TabList>
            <Tab value="book" @click="modalTab='book'">Записать Клиента</Tab>
            <Tab value="break" @click="modalTab='break'" :disabled="!form.time">Перерыв</Tab>
        </TabList>
        <TabPanels>
            <TabPanel value="book">
                <form @submit.prevent="submitCreate" class="flex flex-col gap-4 pt-2">
                    <div class="flex justify-between text-sm bg-gray-50 p-2 rounded">
                        <span>Дата: <span class="font-bold">{{ form.date }}</span></span>
                        <span>Время: <span class="font-bold">{{ form.time }}</span></span>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="font-medium">Услуга</label>
                        <Select 
                            v-model="form.service_id" 
                            :options="services" 
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Выберите услугу" 
                            class="w-full"
                        />
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="font-medium">Клиент</label>
                        <div class="space-y-3">
                            <InputText 
                                v-model="form.client_name" 
                                placeholder="Имя клиента" 
                                class="w-full"
                            />
                            <div class="flex flex-col gap-1">
                                <InputMask 
                                    v-model="form.client_phone" 
                                    mask="89999999999" 
                                    placeholder="89990000000" 
                                    class="w-full"
                                    @input="onPhoneInput" 
                                />
                                <small class="text-gray-500">Если клиент новый, он будет создан автоматически.</small>
                                <small v-if="form.client_phone && !phoneValid" class="text-red-500">Неверный формат телефона</small>
                            </div>
                        </div>
                    </div>

                    <!-- Голосовой ввод внутри модалки -->
                    <div class="border-t pt-3 mt-1">
                        <Button 
                            type="button" 
                            :label="voiceOpen ? 'Скрыть голосовой ввод' : 'Микрофон'" 
                            :icon="voiceOpen ? 'pi pi-chevron-up' : 'pi pi-microphone'"
                            text 
                            size="small"
                            @click="voiceOpen = !voiceOpen" 
                        />
                        
                        <div v-if="voiceOpen" class="mt-2 space-y-2">
                            <div class="relative">
                                <Textarea 
                                    v-model="voiceText" 
                                    rows="3" 
                                    class="w-full text-sm"
                                    placeholder="Диктуйте имя и телефон..." 
                                />
                                <button 
                                    v-if="voiceText" 
                                    type="button" 
                                    class="absolute top-2 right-2 text-gray-400 hover:text-gray-600"
                                    @click="voiceText = ''"
                                >
                                    <i class="pi pi-times"></i>
                                </button>
                            </div>
                            
                            <div class="flex gap-2">
                                <Button 
                                    type="button" 
                                    :severity="isListening ? 'danger' : ''"
                                    :label="isListening ? 'Стоп' : 'Говорить'"
                                    :icon="isListening ? 'pi pi-stop-circle' : 'pi pi-microphone'"
                                    size="small"
                                    @click="toggleRecording"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <Button type="submit" label="Создать запись" icon="pi pi-check" class="w-full" />
                    </div>
                </form>
            </TabPanel>
            
            <TabPanel value="break">
                <form @submit.prevent="submitBreak" class="flex flex-col gap-4 pt-2">
                    <div class="text-sm bg-gray-50 p-2 rounded text-center">
                        Блокировка времени <span class="font-bold">{{ form.time }}</span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="font-medium">Длительность (мин)</label>
                        <Select 
                            v-model="breakDuration" 
                            :options="[15, 30, 45, 60, 90, 120]" 
                            placeholder="Выберите длительность" 
                            class="w-full"
                        />
                    </div>
                    <Button type="submit" label="Заблокировать время" severity="warning" icon="pi pi-lock" class="w-full" />
                </form>
            </TabPanel>
        </TabPanels>
      </Tabs>
    </Dialog>

    <Modal :open="showInfoModal" @close="closeInfo">
      <template #title>{{ info.break_id ? 'Перерыв' : 'Запись' }}</template>
        <div class="space-y-3 text-sm" v-if="!info.break_id">
          <div>Время: <span class="font-mono">{{ info.time }}</span> | Дата: <span class="font-mono">{{ info.date }}</span></div>
          <a :href="'tel:' + info.client?.phone" class="block">Клиент: <span class="font-medium">{{ info.client?.name }}</span> <span class="text-gray-600">{{ info.client?.phone }}</span></a>
          <div>Услуга: <span class="font-medium">{{ info.service?.name }}</span></div>
          <MasterCrmNotes v-if="info.id" :key="info.id" :appointment-id="info.id" :initial-notes="info.private_notes" />
        </div>
        <div class="space-y-3 text-sm" v-else>
           <div>Время: <span class="font-mono">{{ info.time }}</span></div>
           <div>Статус: <span class="font-medium text-red-600">Перерыв</span></div>
        </div>
        <div class="flex flex-wrap gap-2 items-center justify-between mt-4">
          <Button class="bg-red-700" type="button" @click="closeInfo">Закрыть</Button>
          <Button v-if="!info.break_id" class="bg-green-700" type="button" @click="notifyClient">Напомнить клиенту</Button>
          <Button v-if="!info.break_id" class="bg-indigo-600" type="button" @click="cancelAppointment">Удалить</Button>
          <Button v-if="info.break_id" class="bg-indigo-600" type="button" @click="cancelBreak">Удалить перерыв</Button>
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
import { MicrophoneIcon } from '@heroicons/vue/24/solid'
import Modal from '../../components/UI/Modal.vue'
import MasterCrmNotes from './MasterCrmNotes.vue'
import MasterLayout from '../../Layouts/MasterLayout.vue'
import { useOfflineQueue } from '../../Composables/useOfflineQueue'

// PrimeVue Components
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputMask from 'primevue/inputmask'
import Select from 'primevue/select'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import Message from 'primevue/message'
import Textarea from 'primevue/textarea'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'

const props = defineProps({ user: Object })
defineOptions({ layout: MasterLayout })

// --- Offline Queue Logic ---
async function createAppointmentApi(payload) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const res = await apiFetch('/api/appointments', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin',
    })
    if (!res.ok) {
        const data = await res.json().catch(() => ({}))
        const error = new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Ошибка создания записи')
        error.status = res.status
        error.data = data
        throw error
    }
    return res.json()
}

const { isOnline, queue: appointmentQueue, addToQueue: addAppointmentToQueue, isSyncing } = useOfflineQueue('offline_appointments', async (item) => {
    const { _id, ...payload } = item
    await createAppointmentApi(payload)
    // После успешной синхронизации одной записи можно обновить слоты, но лучше сделать это один раз в конце.
    // Но так как мы не знаем, когда конец, можно просто вызывать fetchSlots иногда.
    // В данном случае просто оставим как есть.
})

// Следим за очередью: если она опустела (все синхронизировалось), обновляем слоты
watch(() => appointmentQueue.value.length, (newLen, oldLen) => {
    if (newLen === 0 && oldLen > 0) {
        fetchSlots()
    }
})
// ---------------------------

function getAuthToken() {
  try { return localStorage.getItem('auth_token') || '' } catch (e) { return '' }
}

function authHeaders(extra = {}) {
  const t = getAuthToken()
  const h = { 'X-Requested-With': 'XMLHttpRequest', ...extra }
  if (t) {
      h['Authorization'] = `Bearer ${t}`
  }
  return h
}

async function apiFetch(url, options = {}) {
  const opts = { ...options }
  // При использовании Sanctum с SPA (сессии), нужно убедиться, что мы отправляем credentials: 'same-origin' или 'include'
  // И если мы используем токены, то добавляем заголовок Authorization.
  
  // Если мы используем токены (для мобилки или внешних клиентов):
  opts.headers = authHeaders(opts.headers || {})
  
  // Если мы в браузере и используем cookie-based сессии (Inertia):
  if (!opts.headers['Authorization']) {
      opts.credentials = 'include' // или 'same-origin', если на одном домене
  }
  
  return fetch(url, opts)
}

const selectedDate = ref(new Date())
const slots = ref([])
const isDayOff = ref(false)
const dayOffId = ref(null)
const loading = ref(false)
const services = ref([])
const clients = ref([])
const showModal = ref(false)
const errorMessage = ref('')
const modalTab = ref('book')
const form = ref({ date: '', time: '', service_id: null, client_name: '', client_phone: '', preferred_channels: [] })
const showInfoModal = ref(false)
const info = ref({ id: null, date: '', time: '', client: null, service: null, break_id: null, private_notes: '' })
const fetchError = ref('')
const MIN_PHONE_DIGITS = 5
const MAX_PHONE_DIGITS = 11
const breakDuration = ref(30)
const voiceOpen = ref(false)
const voiceText = ref('')
const voiceError = ref('')
const isListening = ref(false)
const isParsing = ref(false)
let recognition = null

function highlightText(text) {
  if (!text) return ''
  // Простая подсветка ключевых слов (можно улучшить, получая диапазоны от сервера)
  // Здесь мы просто подсвечиваем цифры времени и телефона, и имена с большой буквы
  
  let html = text
    .replace(/</g, '&lt;').replace(/>/g, '&gt;') // Экранируем HTML
    
  // Подсветка времени (14:00, 14 30)
  html = html.replace(/(\d{1,2}[:\s-]\d{2})/g, '<span class="bg-yellow-200 rounded px-0.5">$1</span>')
  
  // Подсветка телефона (последовательность цифр > 5)
  html = html.replace(/(\+?\d[\d\s-]{5,})/g, '<span class="bg-blue-100 rounded px-0.5">$1</span>')
  
  // Подсветка слов с большой буквы (потенциальные имена)
  // Исключаем начало предложения... сложно без NLP.
  // Просто подсветим всё, что похоже на имя
  html = html.replace(/\b([A-ZА-ЯЁ][a-zа-яё]{2,})\b/g, '<span class="bg-green-100 rounded px-0.5">$1</span>')
  
  return html
}

let silenceTimer = null
const SILENCE_TIMEOUT = 2000 // 2 секунды тишины

function toggleRecording() {
  if (isListening.value) {
    stopRecording()
  } else {
    startRecording()
  }
}

function startRecording() {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition
  if (!SpeechRecognition) {
    alert('Ваш браузер не поддерживает голосовой ввод. Попробуйте Chrome или Safari.')
    return
  }

  recognition = new SpeechRecognition()
  recognition.lang = 'ru-RU'
  recognition.continuous = false
  recognition.interimResults = false

  recognition.onstart = () => {
    isListening.value = true
    voiceError.value = ''
    // Сбрасываем таймер при старте
    if (silenceTimer) clearTimeout(silenceTimer)
  }

  recognition.onresult = (event) => {
    const transcript = event.results[0][0].transcript
    voiceText.value = (voiceText.value ? voiceText.value.trim() + ' ' : '') + transcript
    
    // Если распознали что-то, запускаем таймер тишины
    // Но так как continuous=false, запись сама остановится после фразы.
    // Поэтому здесь можно сразу вызывать парсинг, если хотим "быстрый" режим.
    // Или можно перезапускать запись, если хотим continuous.
    // В текущем варианте (continuous=false) браузер сам стопнет запись после фразы.
    // Мы можем в onend проверить: если был текст - парсим.
  }

  recognition.onerror = (event) => {
    console.error('Speech recognition error', event.error)
    if (event.error === 'not-allowed') {
      voiceError.value = 'Доступ к микрофону запрещен.'
    } else if (event.error !== 'no-speech') {
       // no-speech игнорируем, это просто тишина
      voiceError.value = 'Ошибка распознавания: ' + event.error
    }
    stopRecording()
  }

  recognition.onend = () => {
    stopRecording()
    // АВТО-РАСПОЗНАВАНИЕ:
    // Если текст есть и запись остановилась сама (не кнопкой Стоп, хотя тут сложно различить),
    // то пробуем распознать. Чтобы не распознавать случайно, добавим задержку.
    if (voiceText.value.trim().length > 0 && !isParsing.value) {
        // Можно запустить парсинг автоматически
        parseVoice()
    }
  }

  recognition.start()
}

function stopRecording() {
  isListening.value = false
  if (silenceTimer) clearTimeout(silenceTimer)
  if (recognition) {
    recognition.stop()
    recognition = null
  }
}

const phoneValid = computed(() => {
  const len = (form.value.client_phone || '').length
  if (len === 0) return true
  return (len >= MIN_PHONE_DIGITS && len <= MAX_PHONE_DIGITS)
})
function onPhoneInput(e) {
  const val = String(e.target.value || '')
  const digits = val.replace(/\D/g, '').slice(0, MAX_PHONE_DIGITS)
  form.value.client_phone = digits
}

function formatDateLocal(date) {
  if (!(date instanceof Date)) date = new Date(date)
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

async function fetchSlots() {
  loading.value = true
  fetchError.value = ''
  try {
    const dateStr = formatDateLocal(selectedDate.value)
    // Добавим проверку user.id
    if (!props.user?.id) {
        console.error('User ID is missing in props')
        fetchError.value = 'User ID missing'
        return
    }
    const res = await apiFetch(`/api/masters/${props.user.id}/slots?date=${encodeURIComponent(dateStr)}`)
    if (!res.ok) {
        throw new Error(`API Error: ${res.status} ${res.statusText}`)
    }
    const json = await res.json()
    const data = json.data || []
    slots.value = Array.isArray(data) ? data : []
    isDayOff.value = json.meta?.is_day_off || false
    dayOffId.value = json.meta?.day_off_id || null
  } catch (e) {
      console.error('fetchSlots error:', e)
      fetchError.value = e.message
      slots.value = []
  } finally {
    loading.value = false
  }
}

onMounted(fetchSlots)
watch(selectedDate, fetchSlots)

async function fetchServicesAndClients() {
  const [sRes, cRes] = await Promise.all([
    apiFetch(`/api/masters/${props.user.id}/services`, { credentials: 'same-origin' }),
    apiFetch('/api/clients', { credentials: 'same-origin' }),
  ])
  services.value = (await sRes.json()).data ?? []
  clients.value = (await cRes.json()).data ?? []
}

function handleClick(slot) {
  if (slot.is_past) {
    // Если слот прошел и он занят (available=false) - показываем инфо
    // Если слот прошел и он свободен (available=true) - ничего не делаем (просто "нет записи")
    if (!slot.available) {
      openInfoModal(slot)
    }
    return
  }
  // Для будущих слотов
  if (slot.available) {
    openCreateModal(slot)
  } else {
    openInfoModal(slot)
  }
}

function openCreateModal(slot) {
  const dateStr = formatDateLocal(selectedDate.value)
  form.value = { date: dateStr, time: slot.time, service_id: null, client_name: '', client_phone: '', preferred_channels: [] }
  errorMessage.value = ''
  showModal.value = true
  modalTab.value = 'book'
  voiceOpen.value = false
  voiceText.value = ''
  voiceError.value = ''
  suggestedSlots.value = []
  if (services.value.length === 0) {
    fetchServicesAndClients()
  }
}

function openGlobalVoiceModal() {
  // Открываем пустую модалку, без привязки к слоту
  const dateStr = formatDateLocal(selectedDate.value)
  form.value = { date: dateStr, time: '', service_id: null, client_name: '', client_phone: '', preferred_channels: [] }
  errorMessage.value = ''
  showModal.value = true
  modalTab.value = 'book'
  voiceOpen.value = true // Сразу открываем голосовой блок
  voiceText.value = ''
  voiceError.value = ''
  suggestedSlots.value = []
  
  if (services.value.length === 0) {
    fetchServicesAndClients()
  }
}

function closeModal() {
  showModal.value = false
}

async function submitCreate() {
  errorMessage.value = ''
  const payload = { date: form.value.date, time: form.value.time, service_id: form.value.service_id }
  
  // Телефон теперь необязателен. Но если он введен, то валидируем длину.
  if (!form.value.client_phone) {
      errorMessage.value = 'Укажите телефон клиента';
      return
  }
  if (form.value.client_phone && !phoneValid.value) { 
      errorMessage.value = 'Телефон: только цифры, 5–11 символов'; 
      return 
  }
  payload.client_name = form.value.client_name
  payload.client_phone = form.value.client_phone
  payload.preferred_channels = form.value.preferred_channels
  // Добавляем ID мастера (текущего пользователя)
  payload.master_id = props.user?.id

  // OFFLINE CHECK
  if (!isOnline.value) {
    addAppointmentToQueue(payload)
    closeModal()
    // Можно показать уведомление (toast), но пока просто alert или ничего
    alert('Нет интернета. Запись сохранена локально и будет отправлена при появлении сети.')
    return
  }

  try {
    await createAppointmentApi(payload)
    closeModal()
    await fetchSlots()
  } catch (e) {
    errorMessage.value = e.message || 'Ошибка создания записи'
  }
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
  const dateStr = formatDateLocal(selectedDate.value)
  const startTime = form.value.time
  const endTime = addMinutesToTime(form.value.time, breakDurationMin.value)
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  const res = await apiFetch('/api/master/schedule-exceptions', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
    body: JSON.stringify({ type: 'break', date: dateStr, start_time: startTime, end_time: endTime }),
    credentials: 'same-origin',
  })
  if (!res.ok) {
    const d = await res.json().catch(() => ({}))
    errorMessage.value = d.message || 'Ошибка установки перерыва'
    return
  }
  closeModal()
  await fetchSlots()
}

async function makeDayOff() {
  const dateStr = formatDateLocal(selectedDate.value)
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  const res = await apiFetch('/api/master/schedule-exceptions', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
    body: JSON.stringify({ type: 'day_off', date: dateStr }),
    credentials: 'same-origin',
  })
  if (!res.ok) {
    const data = await res.json().catch(() => ({}))
    alert(data.message || 'Ошибка: не удалось сделать выходным')
  }
  await fetchSlots()
}

async function cancelDayOff() {
  if (!dayOffId.value) return
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  const res = await apiFetch(`/api/master/schedule-exceptions/${dayOffId.value}`, {
    method: 'DELETE',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
    credentials: 'same-origin',
  })
  if (!res.ok) {
    const data = await res.json().catch(() => ({}))
    alert(data.message || 'Ошибка: не удалось отменить выходной')
  }
  await fetchSlots()
}

const suggestedSlots = ref([])

function suggestFreeSlots(requestedTime) {
  suggestedSlots.value = []
  if (!slots.value.length) return

  const [reqH, reqM] = requestedTime.split(':').map(Number)
  const reqMinutes = reqH * 60 + reqM

  // Ищем слоты в радиусе +/- 90 минут
  const candidates = slots.value.filter(s => {
      if (!s.available || s.is_past) return false
      const [h, m] = s.time.split(':').map(Number)
      const mins = h * 60 + m
      return Math.abs(mins - reqMinutes) <= 90
  })
  
  // Сортируем по близости к запрошенному времени
  candidates.sort((a, b) => {
      const [ah, am] = a.time.split(':').map(Number)
      const [bh, bm] = b.time.split(':').map(Number)
      const diffA = Math.abs((ah * 60 + am) - reqMinutes)
      const diffB = Math.abs((bh * 60 + bm) - reqMinutes)
      return diffA - diffB
  })

  suggestedSlots.value = candidates.slice(0, 3) // Берем топ-3
}

function selectSuggestedSlot(time) {
    form.value.time = time
    suggestedSlots.value = []
    voiceError.value = ''
}

async function parseVoice() {
  voiceError.value = ''
  const text = voiceText.value.trim()
  if (!text) { voiceError.value = 'Введите или продиктуйте текст'; return }
  
  isParsing.value = true
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const res = await apiFetch('/api/master/parse-voice-command', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({ text }),
      credentials: 'include', // Важно для сессий
    })
    
    if (!res.ok) {
      try { const d = await res.json(); voiceError.value = d.message || 'Ошибка распознавания' } catch (e) { voiceError.value = 'Ошибка распознавания' }
      return
    }

    const data = await res.json().catch(() => ({}))
    const r = data || {}
    
    let changed = false
    if (r.client_name) { form.value.client_name = String(r.client_name); changed = true }
    if (r.phone) { form.value.client_phone = String(r.phone).replace(/\D+/g, '').slice(0, MAX_PHONE_DIGITS); changed = true }
    
    // Логика времени:
    // 1. Если время уже было выбрано (открыли из слота) -> НЕ меняем его (игнорируем голос).
    // 2. Если время пустое (глобальная кнопка) -> берем из голоса и ПРОВЕРЯЕМ занятость.
    
    const isGlobalMode = !form.value.time // Если время изначально пустое - мы в глобальном режиме (или просто не выбрали слот)
    
    // Обработка ДАТЫ (только в глобальном режиме)
    if (isGlobalMode && r.date) {
        const newDate = new Date(r.date)
        // Если дата валидна и отличается от текущей выбранной
        if (!isNaN(newDate) && formatDateLocal(newDate) !== form.value.date) {
             selectedDate.value = newDate // Переключаем календарь
             form.value.date = formatDateLocal(newDate) // Обновляем форму
             changed = true
             
             // ВАЖНО: Ждем загрузки слотов для новой даты!
             // fetchSlots вызовется через watch(selectedDate), но нам нужно дождаться результата здесь.
             await fetchSlots()
        }
    }

    if (isGlobalMode && r.time) {
       const t = String(r.time)
       const m = t.match(/(\d{1,2}:\d{2})/)
       if (m) { 
         const parsedTime = m[1]
         // Проверяем, есть ли такой слот и свободен ли он
         const slot = slots.value.find(s => s.time === parsedTime)
         
         if (slot) {
            if (slot.available) {
               form.value.time = parsedTime
               changed = true
            } else {
               voiceError.value = `Время ${parsedTime} занято.`
               suggestFreeSlots(parsedTime) // Предлагаем ближайшие
            }
         } else {
            // Слот не найден (например, время вне графика)
             voiceError.value = `Время ${parsedTime} не найдено.`
             suggestFreeSlots(parsedTime)
         }
       }
    } else if (!isGlobalMode && r.time) {
       // Мы в режиме слота, но голос вернул время. Мы его игнорируем, но можно показать уведомление.
       // console.log('Игнорируем время из голоса, так как слот уже выбран')
    }

    if (r.service_name && Array.isArray(services.value)) {
      const name = String(r.service_name).toLowerCase().trim()
      const found = services.value.find((s) => String(s.name || '').toLowerCase().includes(name))
      if (found) { form.value.service_id = found.id; changed = true }
    }
    
    if (!changed) {
      voiceError.value = 'Не удалось найти данные (имя, телефон или время) в тексте.'
    }
  } catch (e) {
    console.error(e)
    voiceError.value = 'Ошибка сети или сервера'
  } finally {
    isParsing.value = false
  }
}

async function openInfoModal(slot) {
  const dateStr = formatDateLocal(selectedDate.value)
  info.value = { id: null, date: dateStr, time: slot.time, client: null, service: null, break_id: slot.break_id ?? null }
  
  if (info.value.break_id) {
    showInfoModal.value = true
    return
  }

  const res = await apiFetch(`/api/appointments/at?date=${encodeURIComponent(dateStr)}&time=${encodeURIComponent(slot.time)}&_t=${Date.now()}`, { credentials: 'same-origin' })
  if (res.ok) {
    const data = await res.json()
    const a = data.data ?? data
    console.log('Appointment data loaded:', a)
    
    // ВАЖНО: Сбрасываем объект полностью перед обновлением, чтобы реактивность сработала
    info.value = {
        id: a.id ?? null,
        date: dateStr,
        time: slot.time,
        client: a.client ?? null,
        service: a.service ?? null,
        break_id: null,
        private_notes: a.private_notes ?? ''
    }
    showInfoModal.value = true
  }
}

function closeInfo() {
  showInfoModal.value = false
}

async function notifyClient() {
  if (!info.value.id) return
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  const res = await apiFetch(`/api/appointments/${info.value.id}/notify`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf,
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
  const res = await apiFetch(`/api/appointments/${info.value.id}/cancel`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf,
    },
    credentials: 'same-origin',
  })
  if (res.ok) {
    closeInfo()
    await fetchSlots()
  }
}

async function cancelBreak() {
  if (!info.value.break_id) return
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  const res = await apiFetch(`/api/master/schedule-exceptions/${info.value.break_id}`, {
    method: 'DELETE',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
    credentials: 'same-origin',
  })
  if (res.ok) {
    closeInfo()
    await fetchSlots()
  } else {
    try { const d = await res.json(); /* swallow error UI */ } catch (e) {}
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
