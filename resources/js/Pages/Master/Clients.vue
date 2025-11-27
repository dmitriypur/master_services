<template>
  <div class="p-6">
    <h1 class="text-xl font-semibold">Клиенты</h1>
    <div class="mt-4 flex gap-2">
      <input v-model="createForm.name" placeholder="Имя" class="border rounded p-2" />
      <input v-model="createForm.phone" placeholder="Телефон" class="border rounded p-2" />
      <button class="bg-black text-white px-4 py-2 rounded" @click="createClient">Добавить</button>
    </div>
    <div class="mt-6">
      <table class="min-w-full text-sm">
        <thead>
          <tr><th class="text-left p-2">Имя</th><th class="text-left p-2">Телефон</th><th class="p-2">Действия</th></tr>
        </thead>
        <tbody>
          <tr v-for="c in clients" :key="c.id" class="border-t">
            <td class="p-2">
              <input v-model="c.name" class="border rounded p-1 w-full" />
            </td>
            <td class="p-2">
              <input v-model="c.phone" class="border rounded p-1 w-full" />
            </td>
            <td class="p-2 flex gap-2 justify-end">
              <button class="bg-amber-600 text-white px-3 py-1 rounded" @click="updateClient(c)">Сохранить</button>
              <button class="bg-red-700 text-white px-3 py-1 rounded" @click="deleteClient(c)">Удалить</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="text-red-600 text-sm mt-2" v-if="error">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const clients = ref([])
const error = ref('')
const createForm = ref({ name: '', phone: '' })

async function fetchClients() {
  error.value = ''
  try {
    const res = await fetch('/api/clients', { credentials: 'same-origin' })
    const data = await res.json().catch(() => [])
    clients.value = Array.isArray(data?.data) ? data.data : []
  } catch (e) { error.value = 'Ошибка загрузки' }
}

async function createClient() {
  error.value = ''
  try {
    const res = await fetch('/api/clients', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: JSON.stringify(createForm.value),
    })
    const data = await res.json().catch(() => ({}))
    if (!res.ok) { error.value = data.message || 'Ошибка создания'; return }
    createForm.value = { name: '', phone: '' }
    await fetchClients()
  } catch (e) { error.value = 'Ошибка создания' }
}

async function updateClient(c) {
  error.value = ''
  try {
    const res = await fetch(`/api/clients/${c.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: JSON.stringify({ name: c.name, phone: c.phone }),
    })
    if (!res.ok) { const d = await res.json().catch(() => ({})); error.value = d.message || 'Ошибка сохранения' }
    await fetchClients()
  } catch (e) { error.value = 'Ошибка сохранения' }
}

async function deleteClient(c) {
  error.value = ''
  try {
    const res = await fetch(`/api/clients/${c.id}`, { method: 'DELETE', credentials: 'same-origin' })
    if (!res.ok) { const d = await res.json().catch(() => ({})); error.value = d.message || 'Ошибка удаления' }
    await fetchClients()
  } catch (e) { error.value = 'Ошибка удаления' }
}

onMounted(fetchClients)
</script>

<style scoped>
</style>

