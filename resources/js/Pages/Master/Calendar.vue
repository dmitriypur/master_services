<template>
  <div class="max-w-2xl mx-auto px-0 py-6 space-y-4 pb-20">
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

    <Card class="mb-20 [&_.p-card-body]:!px-4">
        <template #content>
            <div class="flex justify-between items-center mb-4">
                <div class="text-lg font-medium text-gray-700 flex items-center">
                    <span class="font-bold">{{ formatDateLocal(selectedDate) }}</span>
                    <span v-if="isCachedData" class="ml-2 text-xs bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full flex items-center gap-1" title="Данные из кэша">
                        <i class="pi pi-database text-[10px]"></i> Кэш
                    </span>
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
                            s.available && !s.is_past ? 'border-l-4 border-l-green-500' : '',
                            s.is_offline_pending ? '!border-l-yellow-500 bg-yellow-50 !opacity-90 border-dashed' : ''
                        ]"
                        @click="handleClick(s)"
                    >
                        <div class="font-bold text-lg text-gray-800 flex justify-between items-center">
                            {{ s.time }}
                            <i v-if="s.is_offline_pending" class="pi pi-cloud-upload text-yellow-600" title="Ожидает синхронизации"></i>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <template v-if="s.is_past">
                                <span v-if="s.available" class="text-gray-400">Прошел</span>
                                <span v-else class="text-red-600 font-medium">Занят</span>
                            </template>
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

    <div class="fixed bottom-4 left-0 right-0 flex justify-center gap-4 px-4 z-10">
       <Link href="/master/settings">
         <Button label="Настройки" icon="pi pi-cog" severity="success" raised rounded />
       </Link>
       <Link href="/master/clients">
         <Button label="Клиенты" icon="pi pi-users" severity="info" raised rounded />
       </Link>
    </div>

    <Dialog v-model:visible="showModal" modal header="Запись" :style="{ width: '90vw', maxWidth: '500px' }" :contentStyle="{ padding: '0 1rem 1rem 1rem' }" @hide="closeModal">
      <Tabs value="book">
        <TabList>
            <Tab value="book" @click="modalTab='book'">Записать Клиента</Tab>
            <Tab value="break" @click="modalTab='break'" :disabled="!form.time">Перерыв</Tab>
        </TabList>
        <TabPanels :pt="{ root: { class: '!p-0' }, content: { class: '!p-3' } }">
            <TabPanel value="book">
                <form @submit.prevent="submitCreate" class="flex flex-col gap-3">
                    <div class="flex justify-between text-sm bg-gray-50 p-2 rounded">
                        <span>Дата: <span class="font-bold">{{ form.date }}</span></span>
                        <span>Время: <span class="font-bold">{{ form.time }}</span></span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="font-medium text-sm">Услуга</label>
                        <Select 
                            v-model="form.service_id" 
                            :options="services" 
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Выберите услугу" 
                            class="w-full !p-2"
                        />
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="font-medium text-sm">Клиент</label>
                        <div class="space-y-2">
                            <InputText 
                                v-model="form.client_name" 
                                placeholder="Имя клиента" 
                                class="w-full !p-2"
                            />
                            <div class="flex flex-col gap-1">
                                <InputMask 
                                    v-model="form.client_phone" 
                                    mask="89999999999" 
                                    placeholder="89990000000" 
                                    class="w-full !p-2"
                                />
                                <small class="text-gray-500 text-xs">Если клиент новый, он будет создан автоматически.</small>
                                <small v-if="form.client_phone && !phoneValid" class="text-red-500 text-xs">Неверный формат телефона</small>
                            </div>
                        </div>
                    </div>

                    <!-- Голосовой ввод -->
                    <div class="border-t pt-2 mt-1">
                        <Button 
                            type="button" 
                            :label="voiceOpen ? 'Скрыть голосовой ввод' : 'Микрофон'" 
                            :icon="voiceOpen ? 'pi pi-chevron-up' : 'pi pi-microphone'"
                            text 
                            size="small"
                            class="!p-1"
                            @click="voiceOpen = !voiceOpen" 
                        />
                        
                        <div v-if="voiceOpen" class="mt-2 space-y-2">
                            <div class="relative">
                                <Textarea 
                                    v-model="voiceText" 
                                    rows="2" 
                                    class="w-full text-sm !p-2"
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
                             <div v-if="voiceError" class="text-red-500 text-xs">{{ voiceError }}</div>
                        </div>
                    </div>
                    
                    <div v-if="errorMessage" class="text-red-500 text-sm">{{ errorMessage }}</div>

                    <div class="pt-1">
                        <Button type="submit" label="Создать запись" icon="pi pi-check" class="w-full" />
                    </div>
                </form>
            </TabPanel>
            
            <TabPanel value="break">
                <form @submit.prevent="submitBreak(formatDateLocal(selectedDate))" class="flex flex-col gap-4 pt-2">
                    <div class="text-sm bg-gray-50 p-2 rounded text-center">
                        Блокировка времени <span class="font-bold">{{ form.time }}</span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="font-medium text-sm">Длительность (мин)</label>
                        <Select 
                            v-model="breakDuration" 
                            :options="[15, 30, 45, 60, 90, 120]" 
                            placeholder="Выберите длительность" 
                            class="w-full !p-2"
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
import { ref, watch, onMounted } from 'vue'
import { ru as ruLocale } from 'date-fns/locale'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import Modal from '../../components/UI/Modal.vue'
import MasterCrmNotes from './MasterCrmNotes.vue'
import MasterLayout from '../../Layouts/MasterLayout.vue'

// PrimeVue
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

import { useOfflineQueue } from '../../Composables/useOfflineQueue'
import { useMasterCalendar } from '../../Composables/useMasterCalendar'
import { useAppointmentForm } from '../../Composables/useAppointmentForm'
import { useVoiceAssistant } from '../../Composables/useVoiceAssistant'

const props = defineProps({ user: Object })
defineOptions({ layout: MasterLayout })

const services = ref([])
const clients = ref([])

// 1. Offline Queue
const { isOnline, queue: appointmentQueue, addToQueue } = useOfflineQueue('offline_appointments', async (item) => {
    const { _id, ...payload } = item
    await createAppointmentApi(payload)
})

// 2. Calendar Logic
const {
    selectedDate, slots, isDayOff, dayOffId, loading, isCachedData,
    formatDateLocal, fetchSlots, makeDayOff, cancelDayOff, apiFetch
} = useMasterCalendar(props, appointmentQueue, services)

// 3. Form Logic
const {
    showModal, modalTab, form, errorMessage, breakDuration, phoneValid,
    openCreateModal: openCreateModalFn, submitCreate, submitBreak, createAppointmentApi
} = useAppointmentForm(props, isOnline, addToQueue, fetchSlots, apiFetch)

// 4. Voice Logic
const {
    voiceOpen, voiceText, voiceError, isListening, 
    toggleRecording, parseVoice
} = useVoiceAssistant(apiFetch, services, slots, selectedDate, form, fetchSlots, formatDateLocal)

// 5. Helpers
async function fetchServicesAndClients() {
  const cacheKeyServices = `services_${props.user?.id}`
  const cacheKeyClients = `clients_${props.user?.id}`
  const cachedServices = localStorage.getItem(cacheKeyServices)
  const cachedClients = localStorage.getItem(cacheKeyClients)
  if (cachedServices) { try { services.value = JSON.parse(cachedServices) } catch(e) {} }
  if (cachedClients) { try { clients.value = JSON.parse(cachedClients) } catch(e) {} }

  if (!navigator.onLine) return

  try {
      const [sRes, cRes] = await Promise.all([
        apiFetch(`/api/masters/${props.user.id}/services`, { credentials: 'same-origin' }),
        apiFetch('/api/clients', { credentials: 'same-origin' }),
      ])
      if (sRes.ok) {
          const sData = (await sRes.json()).data ?? []
          services.value = sData
          localStorage.setItem(cacheKeyServices, JSON.stringify(sData))
      }
      if (cRes.ok) {
          const cData = (await cRes.json()).data ?? []
          clients.value = cData
          localStorage.setItem(cacheKeyClients, JSON.stringify(cData))
      }
  } catch (e) {
      console.error('Error fetching services/clients', e)
  }
}

function handleClick(slot) {
  if (slot.is_past) {
    if (!slot.available) openInfoModal(slot)
    return
  }
  if (slot.available) {
    openCreateModalFn(slot, formatDateLocal(selectedDate.value), services.value, fetchServicesAndClients)
  } else {
    openInfoModal(slot)
  }
}

function openGlobalVoiceModal() {
  // Setup form for global mode
  form.value = { date: formatDateLocal(selectedDate.value), time: '', service_id: null, client_name: '', client_phone: '', preferred_channels: [] }
  errorMessage.value = ''
  showModal.value = true
  modalTab.value = 'book'
  voiceOpen.value = true
  voiceText.value = ''
  voiceError.value = ''
  
  if (services.value.length === 0) fetchServicesAndClients()
}

function closeModal() {
  showModal.value = false
}

// Info Modal Logic
const showInfoModal = ref(false)
const info = ref({ id: null, date: '', time: '', client: null, service: null, break_id: null, private_notes: '' })

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

function closeInfo() { showInfoModal.value = false }

async function notifyClient() {
  if (!info.value.id) return
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  const res = await apiFetch(`/api/appointments/${info.value.id}/notify`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
    credentials: 'same-origin',
  })
  if (res.ok) {
    const data = await res.json()
    const url = data.whatsapp_url
    if (url) try { window.open(url, '_blank') } catch (e) { window.location.href = url }
  }
}

async function cancelAppointment() {
  if (!info.value.id) return
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  const res = await apiFetch(`/api/appointments/${info.value.id}/cancel`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
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
  }
}
</script>

<style scoped>
:deep(.booking-picker) {
  width: 100%;
  flex-direction: column;
    div{ width: 100%; }
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
