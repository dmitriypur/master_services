<template>
  <div class="max-w-2xl mx-auto px-4 py-6">
    <div class="mb-4 flex items-center gap-3">
      <Link href="/master/calendar" class="inline-flex text-sm items-center rounded bg-green-500 text-white px-3 py-1.5">Календарь</Link>
      <Link href="/master/clients" class="inline-flex text-sm items-center rounded bg-sky-500 text-white px-3 py-1.5">Клиенты</Link>
    </div>
    <h1 class="text-2xl font-semibold mb-6">Настройки мастера</h1>
    <form @submit.prevent="submit" class="space-y-6">
      <div>
        <label class="block text-sm font-medium mb-2">Город</label>
        <select v-model="form.city_id" class="block w-full rounded border border-gray-300 px-3 py-2">
          <option :value="null">Выберите город</option>
          <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <p v-if="form.errors.city_id" class="text-red-600 text-sm mt-1">{{ form.errors.city_id }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium mb-2">Адрес</label>
        <input v-model="form.address" type="text" class="block w-full rounded border border-gray-300 px-3 py-2" />
        <p v-if="form.errors.address" class="text-red-600 text-sm mt-1">{{ form.errors.address }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium mb-2">Телефон</label>
        <input v-model="form.phone" type="text" inputmode="numeric" maxlength="11" class="block w-full rounded border border-gray-300 px-3 py-2" />
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
          <input v-model="form.work_time_from" type="time" step="60" class="block w-full rounded border border-gray-300 px-3 py-2" />
          <p v-if="form.errors.work_time_from" class="text-red-600 text-sm mt-1">{{ form.errors.work_time_from }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2">Время до</label>
          <input v-model="form.work_time_to" type="time" step="60" class="block w-full rounded border border-gray-300 px-3 py-2" />
          <p v-if="form.errors.work_time_to" class="text-red-600 text-sm mt-1">{{ form.errors.work_time_to }}</p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-2">Длительность слота</label>
        <select v-model.number="form.slot_duration_min" class="block w-full rounded border border-gray-300 px-3 py-2">
          <option :value="15">15 мин</option>
          <option :value="30">30 мин</option>
          <option :value="60">60 мин</option>
        </select>
        <p v-if="form.errors.slot_duration_min" class="text-red-600 text-sm mt-1">{{ form.errors.slot_duration_min }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium mb-2">Услуги</label>
        <!-- Новый каскадный селект -->
        <CascadingServiceSelect v-model="form.services" />
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
import MasterLayout from '../../Layouts/MasterLayout.vue'
import CascadingServiceSelect from '../../components/UI/CascadingServiceSelect.vue'

const props = defineProps({ user: Object, cities: Array, settings: Object, servicesOptions: Array, selectedServiceIds: Array })
defineOptions({ layout: MasterLayout })

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

function submit() {
  form.put('/master/settings')
}
</script>

<style scoped>
</style>
