<template>
  <div class="max-w-2xl mx-auto px-4 py-6 space-y-4 pb-20">
    <h1 class="text-xl font-semibold mb-4">Клиенты</h1>

    <!-- Форма создания -->
    <Card>
        <template #title>Новый клиент</template>
        <template #content>
            <div class="flex flex-col gap-3">
                <InputText v-model="createForm.name" placeholder="Имя" class="w-full" />
                <InputMask v-model="createForm.phone" mask="89999999999" placeholder="Телефон" class="w-full" />
                <Button label="Добавить" icon="pi pi-plus" @click="createClient" :loading="creating" />
            </div>
            <small v-if="error" class="text-red-500 mt-2 block">{{ error }}</small>
        </template>
    </Card>

    <!-- Список клиентов -->
    <div class="space-y-3">
        <div v-if="loading" class="text-center py-4 text-gray-500">
            <i class="pi pi-spin pi-spinner text-2xl"></i>
        </div>
        <div v-else-if="clients.length === 0" class="text-center py-8 text-gray-500 bg-white rounded-lg border">
            Клиентов пока нет
        </div>
        <div v-else v-for="c in clients" :key="c.id" class="bg-white p-3 rounded-lg border-gray-300 shadow-sm flex flex-col gap-2">
            <div class="flex items-center gap-2">
                <InputText v-model="c.name" class="flex-1 !py-1.5 !px-2 !text-sm" placeholder="Имя" />
                <InputMask v-model="c.phone" mask="89999999999" class="w-32 !py-1.5 !px-2 !text-sm" placeholder="Телефон" />
            </div>
            <div class="flex justify-end gap-2">
                <Button icon="pi pi-check" size="small" severity="success" text rounded aria-label="Сохранить" @click="updateClient(c)" />
                <Button icon="pi pi-trash" size="small" severity="danger" text rounded aria-label="Удалить" @click="confirmDelete(c)" />
            </div>
        </div>
    </div>

    <!-- Плавающая панель навигации внизу -->
    <div class="fixed bottom-4 left-0 right-0 flex justify-center gap-4 px-4 z-10">
       <Link href="/master/settings">
         <Button label="Настройки" icon="pi pi-cog" severity="success" raised rounded />
       </Link>
       <Link href="/master/calendar">
         <Button label="Календарь" icon="pi pi-calendar" severity="info" raised rounded />
       </Link>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import MasterLayout from '../../Layouts/MasterLayout.vue'

// PrimeVue Components
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputMask from 'primevue/inputmask'
import Card from 'primevue/card'

defineOptions({ layout: MasterLayout })

const clients = ref([])
const error = ref('')
const createForm = ref({ name: '', phone: '' })
const loading = ref(false)
const creating = ref(false)

async function fetchClients() {
  loading.value = true
  error.value = ''
  try {
    const res = await fetch('/api/clients', { credentials: 'same-origin' })
    const data = await res.json().catch(() => [])
    clients.value = Array.isArray(data?.data) ? data.data : []
  } catch (e) { error.value = 'Ошибка загрузки' }
  finally { loading.value = false }
}

async function createClient() {
  error.value = ''
  creating.value = true
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  try {
    const res = await fetch('/api/clients', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf
      },
      credentials: 'same-origin',
      body: JSON.stringify(createForm.value),
    })
    const data = await res.json().catch(() => ({}))
    if (!res.ok) { error.value = data.message || 'Ошибка создания'; return }
    createForm.value = { name: '', phone: '' }
    await fetchClients()
  } catch (e) { error.value = 'Ошибка создания' }
  finally { creating.value = false }
}

async function updateClient(c) {
  error.value = ''
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  try {
    const res = await fetch(`/api/clients/${c.id}`, {
      method: 'PUT',
      headers: { 
        'Content-Type': 'application/json', 
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf
      },
      credentials: 'same-origin',
      body: JSON.stringify({ name: c.name, phone: c.phone }),
    })
    if (!res.ok) { const d = await res.json().catch(() => ({})); error.value = d.message || 'Ошибка сохранения' }
    // Не перезагружаем весь список, чтобы не сбивать фокус, если вдруг он нужен, хотя тут кнопки.
    // Можно показать toast
  } catch (e) { error.value = 'Ошибка сохранения' }
}

async function confirmDelete(c) {
    if (confirm('Вы уверены, что хотите удалить клиента?')) {
        await deleteClient(c)
    }
}

async function deleteClient(c) {
  error.value = ''
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  try {
    const res = await fetch(`/api/clients/${c.id}`, { 
      method: 'DELETE', 
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf
      },
      credentials: 'same-origin' 
    })
    if (!res.ok) { const d = await res.json().catch(() => ({})); error.value = d.message || 'Ошибка удаления' }
    await fetchClients()
  } catch (e) { error.value = 'Ошибка удаления' }
}

onMounted(fetchClients)
</script>

<style scoped>
</style>
