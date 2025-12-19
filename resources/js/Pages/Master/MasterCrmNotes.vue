<template>
  <div class="space-y-3">
    <label class="block text-sm font-medium">Заметки мастера</label>
    <textarea v-model="notes" rows="4" class="block w-full rounded border px-3 py-2" />
    <div class="flex items-center justify-between">
      <div class="text-sm text-red-600" v-if="error">{{ error }}</div>
      <button class="inline-flex items-center rounded bg-indigo-700 text-white px-3 py-1.5" :disabled="saving" @click="save">Сохранить</button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'

const props = defineProps({ 
  appointmentId: { type: [String, Number], required: true },
  initialNotes: { type: String, default: '' }
})
const notes = ref(props.initialNotes)
console.log('MasterCrmNotes mounted, initialNotes:', props.initialNotes)
const error = ref('')
const saving = ref(false)

watch(() => props.initialNotes, (newVal) => {
  console.log('MasterCrmNotes initialNotes changed:', newVal)
  notes.value = newVal || ''
})

function getAuthToken() {
  try { return localStorage.getItem('auth_token') || '' } catch (e) { return '' }
}
function authHeaders(extra = {}) {
  const t = getAuthToken()
  const h = { 'X-Requested-With': 'XMLHttpRequest', ...extra }
  if (t) h['Authorization'] = `Bearer ${t}`
  return h
}

async function load() {
  // Загрузка теперь происходит через props.initialNotes из родительского компонента
}

async function save() {
  error.value = ''
  saving.value = true
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const res = await fetch(`/api/master/appointment/${props.appointmentId}/notes`, {
      method: 'POST',
      headers: authHeaders({ 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }),
      credentials: 'same-origin',
      body: JSON.stringify({ private_notes: notes.value }),
    })
    const d = await res.json().catch(() => ({}))
    if (!res.ok) { error.value = d.message || 'Ошибка сохранения'; return }
  } finally {
    saving.value = false
  }
}

onMounted(load)
watch(() => props.appointmentId, load)
</script>

<style scoped>
</style>
