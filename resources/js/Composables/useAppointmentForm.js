import { ref, computed } from 'vue'

export function useAppointmentForm(props, isOnline, addAppointmentToQueue, fetchSlots, apiFetch) {
    const showModal = ref(false)
    const modalTab = ref('book')
    const form = ref({ date: '', time: '', service_id: null, client_name: '', client_phone: '', preferred_channels: [] })
    const errorMessage = ref('')
    const breakDuration = ref(30)
    
    const MIN_PHONE_DIGITS = 5
    const MAX_PHONE_DIGITS = 11

    const phoneValid = computed(() => {
        const len = (form.value.client_phone || '').length
        if (len === 0) return true
        return (len >= MIN_PHONE_DIGITS && len <= MAX_PHONE_DIGITS)
    })

    function openCreateModal(slot, dateStr, services, fetchServicesAndClients) {
        form.value = { date: dateStr, time: slot.time, service_id: null, client_name: '', client_phone: '', preferred_channels: [] }
        errorMessage.value = ''
        showModal.value = true
        modalTab.value = 'book'
        
        if (services.length === 0) {
            fetchServicesAndClients()
        }
    }

    async function createAppointmentApi(payload) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        const res = await apiFetch('/api/appointments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin',
        })
        if (!res.ok) {
            const data = await res.json().catch(() => ({}))
            const error = new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Ошибка создания записи')
            error.status = res.status
            error.data = data
            throw error
        }
        return res.json()
    }

    async function submitCreate() {
        errorMessage.value = ''
        const payload = { date: form.value.date, time: form.value.time, service_id: form.value.service_id }
        
        if (!form.value.client_phone) {
            errorMessage.value = 'Укажите телефон клиента';
            return
        }
        if (form.value.client_phone && !phoneValid.value) { 
            errorMessage.value = 'Телефон: только цифры, 5–11 символов'; 
            return 
        }
        payload.client_name = form.value.client_name
        payload.client_phone = form.value.client_phone
        payload.preferred_channels = form.value.preferred_channels
        payload.master_id = props.user?.id
      
        if (!isOnline.value) {
          addAppointmentToQueue(payload)
          showModal.value = false
          alert('Нет интернета. Запись сохранена локально и будет отправлена при появлении сети.')
          return
        }
      
        try {
          await createAppointmentApi(payload)
          showModal.value = false
          await fetchSlots()
        } catch (e) {
          errorMessage.value = e.message || 'Ошибка создания записи'
        }
    }

    function addMinutesToTime(timeStr, minutes) {
        const [hh, mm] = String(timeStr || '00:00').split(':').map((v) => parseInt(v, 10) || 0)
        let total = hh * 60 + mm + (minutes || 0)
        if (total < 0) total = 0
        const endH = Math.min(23, Math.floor(total / 60))
        const endM = total % 60
        return String(endH).padStart(2, '0') + ':' + String(endM).padStart(2, '0')
    }
      
    async function submitBreak(selectedDateStr) {
        const dateStr = selectedDateStr
        const startTime = form.value.time
        const endTime = addMinutesToTime(form.value.time, breakDuration.value)
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        const res = await apiFetch('/api/master/schedule-exceptions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ type: 'break', date: dateStr, start_time: startTime, end_time: endTime }),
            credentials: 'same-origin',
        })
        if (!res.ok) {
            const d = await res.json().catch(() => ({}))
            errorMessage.value = d.message || 'Ошибка установки перерыва'
            return
        }
        showModal.value = false
        await fetchSlots()
    }

    return {
        showModal,
        modalTab,
        form,
        errorMessage,
        breakDuration,
        phoneValid,
        openCreateModal,
        submitCreate,
        submitBreak,
        createAppointmentApi // Export if needed elsewhere
    }
}
