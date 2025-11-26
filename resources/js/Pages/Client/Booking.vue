<template>
  <div class="max-w-3xl mx-auto p-6 space-y-6">
    <h1 class="text-2xl font-semibold">Поиск мастеров</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
      <div>
        <label class="block text-sm font-medium mb-2">Город</label>
        <select v-model.number="selectedCityId" class="block w-full rounded border px-3 py-2">
          <option :value="null">Не выбран</option>
          <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-2">Услуга</label>
        <select v-model.number="selectedServiceId" class="block w-full rounded border px-3 py-2">
          <option :value="null">Не выбрана</option>
          <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </div>
      <div>
        <button @click="search" class="inline-flex items-center rounded bg-black text-white px-4 py-2">Найти мастеров</button>
      </div>
    </div>

    <div v-if="loading" class="text-gray-600">Загрузка...</div>

    <div v-if="!loading && masters.length === 0" class="text-gray-600">Нет результатов</div>

    <ul v-if="masters.length > 0" class="space-y-4">
      <li v-for="m in masters" :key="m.id" class="rounded border p-4">
        <div class="font-semibold">{{ m.name }}</div>
        <div class="text-sm text-gray-700">{{ m.city?.name ?? '—' }}</div>
        <div class="text-sm text-gray-700">{{ m.address ?? '—' }}</div>
        <div class="text-sm text-gray-700">Услуги: {{ (m.services || []).map(s => s.name).join(', ') }}</div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({ cities: Array, services: Array })
const selectedCityId = ref(null)
const selectedServiceId = ref(null)
const masters = ref([])
const loading = ref(false)

async function search() {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (selectedCityId.value) params.set('city_id', String(selectedCityId.value))
    if (selectedServiceId.value) params.set('service_id', String(selectedServiceId.value))
    const res = await fetch(`/api/masters?${params.toString()}`)
    const json = await res.json()
    masters.value = Array.isArray(json) ? json : (json.data ?? [])
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
</style>