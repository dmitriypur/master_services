<template>
  <div class="max-w-2xl mx-auto py-4">
    <div class="mb-4 flex items-center gap-3">
      <Link href="/master/calendar" class="inline-flex text-sm items-center rounded bg-gray-900 text-white px-3 py-1.5">Календарь</Link>
      <Link href="/master/clients" class="inline-flex text-sm items-center rounded bg-gray-900 text-white px-3 py-1.5">Клиенты</Link>
      <span class="text-gray-500 text-sm">Настройки</span>
    </div>
    <h1 class="text-2xl font-semibold mb-6">Настройки мастера</h1>
    <form @submit.prevent="submit" class="space-y-6">
      <div>
        <label class="block text-sm font-medium mb-2">Город</label>
        <select v-model="form.city_id" class="block w-full rounded border px-3 py-2">
          <option :value="null">Выберите город</option>
          <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <p v-if="form.errors.city_id" class="text-red-600 text-sm mt-1">{{ form.errors.city_id }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium mb-2">Адрес</label>
        <input v-model="form.address" type="text" class="block w-full rounded border px-3 py-2" />
        <p v-if="form.errors.address" class="text-red-600 text-sm mt-1">{{ form.errors.address }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium mb-2">Телефон</label>
        <input v-model="form.phone" type="text" inputmode="numeric" maxlength="11" class="block w-full rounded border px-3 py-2" />
        <p v-if="form.errors.phone" class="text-red-600 text-sm mt-1">{{ form.errors.phone }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium mb-2">Дни недели</label>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="d in weekDays"
            :key="d.key"
            type="button"
            class="px-3 py-1.5 rounded-md text-sm border"
            :class="form.work_days.includes(d.key) ? 'bg-sky-700 text-white border-sky-700' : 'bg-white text-gray-700 border-gray-300'"
            @click="toggleDay(d.key)"
          >
            {{ d.label }}
          </button>
        </div>
        <p v-if="form.errors.work_days" class="text-red-600 text-sm mt-1">{{ form.errors.work_days }}</p>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-2">Время с</label>
          <input v-model="form.work_time_from" type="time" step="60" class="block w-full rounded border px-3 py-2" />
          <p v-if="form.errors.work_time_from" class="text-red-600 text-sm mt-1">{{ form.errors.work_time_from }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2">Время до</label>
          <input v-model="form.work_time_to" type="time" step="60" class="block w-full rounded border px-3 py-2" />
          <p v-if="form.errors.work_time_to" class="text-red-600 text-sm mt-1">{{ form.errors.work_time_to }}</p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-2">Длительность слота</label>
        <select v-model.number="form.slot_duration_min" class="block w-full rounded border px-3 py-2">
          <option :value="15">15 мин</option>
          <option :value="30">30 мин</option>
          <option :value="60">60 мин</option>
        </select>
        <p v-if="form.errors.slot_duration_min" class="text-red-600 text-sm mt-1">{{ form.errors.slot_duration_min }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium mb-2">Услуги</label>
        <div class="relative">
          <button type="button" class="block w-full rounded border px-3 py-2 text-left bg-white" @click="servicesOpen = !servicesOpen">
            <span v-if="form.services.length === 0" class="text-gray-500">Выберите услуги</span>
            <span v-else class="flex flex-wrap gap-2">
              <span v-for="s in selectedServices" :key="s.id" class="inline-flex items-center gap-1 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-1 text-xs">
                {{ s.name }}
                <span class="cursor-pointer" @click.stop="removeService(s.id)">×</span>
              </span>
            </span>
          </button>
          <div v-if="servicesOpen" class="absolute z-10 mt-2 w-full rounded border bg-white shadow">
            <div class="p-2 border-b">
              <input v-model="serviceQuery" type="text" placeholder="Поиск" class="block w-full rounded border px-2 py-1" />
            </div>
            <div class="max-h-56 overflow-auto">
              <button
                v-for="s in filteredServices"
                :key="s.id"
                type="button"
                class="flex w-full items-center justify-between px-3 py-2 hover:bg-gray-50"
                @click="toggleService(s.id)"
              >
                <span>{{ s.name }}</span>
                <input type="checkbox" :checked="form.services.includes(s.id)" readonly />
              </button>
            </div>
            <div class="p-2 border-t text-right">
              <button type="button" class="inline-flex items-center rounded bg-indigo-700 text-white px-3 py-1.5" @click="servicesOpen=false">Готово</button>
            </div>
          </div>
        </div>
        <p v-if="form.errors.services" class="text-red-600 text-sm mt-1">{{ form.errors.services }}</p>
      </div>

      <div>
        <button type="submit" class="flex items-center justify-center w-full rounded-lg bg-indigo-700 text-white text-center px-4 py-2">Сохранить</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
const props = defineProps({ user: Object, cities: Array, settings: Object, servicesOptions: Array, selectedServiceIds: Array })

const form = useForm({
  city_id: props.user?.city_id ?? null,
  address: props.settings?.address ?? '',
  phone: props.user?.phone ?? '',
  work_days: props.settings?.work_days ?? [],
  work_time_from: props.settings?.work_time_from ?? '',
  work_time_to: props.settings?.work_time_to ?? '',
  slot_duration_min: props.settings?.slot_duration_min ?? 30,
  services: props.selectedServiceIds ?? [],
})

const weekDays = [
  { key: 'mon', label: 'Пн' },
  { key: 'tue', label: 'Вт' },
  { key: 'wed', label: 'Ср' },
  { key: 'thu', label: 'Чт' },
  { key: 'fri', label: 'Пт' },
  { key: 'sat', label: 'Сб' },
  { key: 'sun', label: 'Вс' },
]

function toggleDay(key) {
  const idx = form.work_days.indexOf(key)
  if (idx >= 0) {
    form.work_days.splice(idx, 1)
  } else {
    form.work_days.push(key)
  }
}

const serviceReduce = (s) => s.id

import { ref, computed } from 'vue'
const servicesOpen = ref(false)
const serviceQuery = ref('')
const filteredServices = computed(() => {
  const q = serviceQuery.value.trim().toLowerCase()
  if (!q) return props.servicesOptions || []
  return (props.servicesOptions || []).filter(s => String(s.name || '').toLowerCase().includes(q))
})
const selectedServices = computed(() => {
  const ids = new Set(form.services)
  return (props.servicesOptions || []).filter(s => ids.has(s.id))
})
function toggleService(id) {
  const i = form.services.indexOf(id)
  if (i >= 0) {
    form.services.splice(i, 1)
  } else {
    form.services.push(id)
  }
}
function removeService(id) {
  const i = form.services.indexOf(id)
  if (i >= 0) form.services.splice(i, 1)
}

function submit() {
  form.put('/master/settings')
}
</script>

<style scoped>
</style>
