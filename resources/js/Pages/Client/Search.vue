<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-semibold">Поиск мастеров</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
      <div>
        <label class="block text-sm font-medium mb-2">Город1</label>
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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" v-if="masters.length > 0">
      <MasterCard v-for="m in masters" :key="m.id" :master="m" @book="goBook(m)" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import MasterCard from './MasterCard.vue'

const cities = ref([])
const services = ref([])
const masters = ref([])
const selectedCityId = ref(null)
const selectedServiceId = ref(null)
const loading = ref(false)

async function loadFilters() {
  try {
    const [cRes, sRes] = await Promise.all([
      fetch('/api/cities'),
      fetch('/api/services'),
    ])
    cities.value = await cRes.json().then(d => d.data ?? d)
    services.value = await sRes.json().then(d => d.data ?? d)
  } catch (e) {}
}

async function search() {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (selectedCityId.value) params.set('city_id', String(selectedCityId.value))
    if (selectedServiceId.value) params.set('service_id', String(selectedServiceId.value))
    const res = await fetch(`/api/masters?${params.toString()}`)
    masters.value = await res.json().then(d => Array.isArray(d) ? d : (d.data ?? []))
  } finally {
    loading.value = false
  }
}

function goBook(m) {
  window.location.href = `/masters/${m.id}/book`
}

onMounted(loadFilters)
</script>

<style scoped>
</style>
