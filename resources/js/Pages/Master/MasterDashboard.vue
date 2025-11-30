<template>
  <div class="max-w-5xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">Аналитика</h1>
    <div v-if="loading" class="text-gray-600">Загрузка…</div>
    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="rounded-xl border p-4">
        <div class="text-sm text-gray-600">Доход</div>
        <div class="text-2xl font-semibold">{{ fmtCurrency(metrics.total_revenue) }}</div>
      </div>
      <div class="rounded-xl border p-4">
        <div class="text-sm text-gray-600">Записей</div>
        <div class="text-2xl font-semibold">{{ metrics.total_appointments }}</div>
      </div>
      <div class="rounded-xl border p-4">
        <div class="text-sm text-gray-600">Отмены</div>
        <div class="text-2xl font-semibold">{{ fmtPercent(metrics.cancellation_rate) }}</div>
      </div>
    </div>
    <div v-if="error" class="mt-3 text-red-600 text-sm">{{ error }}</div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import MasterLayout from '../../Layouts/MasterLayout.vue'
defineOptions({ layout: MasterLayout })

function getAuthToken() {
  try { return localStorage.getItem('auth_token') || '' } catch (e) { return '' }
}
function authHeaders(extra = {}) {
  const t = getAuthToken()
  const h = { 'X-Requested-With': 'XMLHttpRequest', ...extra }
  if (t) h['Authorization'] = `Bearer ${t}`
  return h
}

const metrics = ref({ total_revenue: 0, total_appointments: 0, cancellation_rate: 0 })
const loading = ref(false)
const error = ref('')

function fmtCurrency(v) {
  const n = Number(v || 0)
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(n)
}
function fmtPercent(v) {
  const n = Number(v || 0)
  return new Intl.NumberFormat('ru-RU', { style: 'percent', maximumFractionDigits: 1 }).format(n)
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await fetch('/api/master/analytics/dashboard', { headers: authHeaders(), credentials: 'same-origin' })
    if (!res.ok) {
      const d = await res.json().catch(() => ({}))
      error.value = d.message || 'Ошибка загрузки'
      return
    }
    const data = await res.json()
    metrics.value = data?.data ?? data
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
</style>
