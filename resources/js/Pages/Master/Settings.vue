<template>
  <div class="max-w-2xl mx-auto py-8 px-4">
    <div class="mb-4 flex items-center gap-3">
      <Link href="/master/calendar" class="inline-flex items-center rounded bg-gray-900 text-white px-3 py-1.5">Календарь</Link>
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
        <label class="block text-sm font-medium mb-2">Дни недели</label>
        <div class="grid grid-cols-3 gap-2">
          <label class="flex items-center gap-2"><input type="checkbox" value="mon" v-model="form.work_days" /> Пн</label>
          <label class="flex items-center gap-2"><input type="checkbox" value="tue" v-model="form.work_days" /> Вт</label>
          <label class="flex items-center gap-2"><input type="checkbox" value="wed" v-model="form.work_days" /> Ср</label>
          <label class="flex items-center gap-2"><input type="checkbox" value="thu" v-model="form.work_days" /> Чт</label>
          <label class="flex items-center gap-2"><input type="checkbox" value="fri" v-model="form.work_days" /> Пт</label>
          <label class="flex items-center gap-2"><input type="checkbox" value="sat" v-model="form.work_days" /> Сб</label>
          <label class="flex items-center gap-2"><input type="checkbox" value="sun" v-model="form.work_days" /> Вс</label>
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
        <select v-model="form.services" multiple class="block w-full rounded border px-3 py-2 h-40">
          <option v-for="s in servicesOptions" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
        <p v-if="form.errors.services" class="text-red-600 text-sm mt-1">{{ form.errors.services }}</p>
      </div>

      <div>
        <button type="submit" class="inline-flex items-center rounded bg-black text-white px-4 py-2">Сохранить</button>
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
  work_days: props.settings?.work_days ?? [],
  work_time_from: props.settings?.work_time_from ?? '',
  work_time_to: props.settings?.work_time_to ?? '',
  slot_duration_min: props.settings?.slot_duration_min ?? 30,
  services: props.selectedServiceIds ?? [],
})

function submit() {
  form.put('/master/settings')
}
</script>

<style scoped>
</style>