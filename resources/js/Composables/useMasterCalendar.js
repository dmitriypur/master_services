import { ref, watch, onMounted } from 'vue'
import { ru as ruLocale } from 'date-fns/locale'

export function useMasterCalendar(props, appointmentQueue, services) {
    const selectedDate = ref(new Date())
    const slots = ref([])
    const isDayOff = ref(false)
    const dayOffId = ref(null)
    const loading = ref(false)
    const fetchError = ref('')
    const isCachedData = ref(false)

    function formatDateLocal(date) {
        if (!(date instanceof Date)) date = new Date(date)
        const y = date.getFullYear()
        const m = String(date.getMonth() + 1).padStart(2, '0')
        const d = String(date.getDate()).padStart(2, '0')
        return `${y}-${m}-${d}`
    }

    function getAuthToken() {
        try { return localStorage.getItem('auth_token') || '' } catch (e) { return '' }
    }
      
    function authHeaders(extra = {}) {
        const t = getAuthToken()
        const h = { 'X-Requested-With': 'XMLHttpRequest', ...extra }
        if (t) {
            h['Authorization'] = `Bearer ${t}`
        }
        return h
    }
      
    async function apiFetch(url, options = {}) {
        const opts = { ...options }
        opts.headers = authHeaders(opts.headers || {})
        if (!opts.headers['Authorization']) {
            opts.credentials = 'include'
        }
        return fetch(url, opts)
    }

    // Применяем локальные изменения из очереди к отображаемым слотам
    function applyQueueToSlots() {
        if (!slots.value.length || !appointmentQueue.value.length) return
        
        const currentDate = formatDateLocal(selectedDate.value)
        
        appointmentQueue.value.forEach(item => {
            if (item.date === currentDate) {
                const slotIndex = slots.value.findIndex(s => s.time === item.time)
                if (slotIndex !== -1) {
                    slots.value[slotIndex].available = false
                    slots.value[slotIndex].client = { name: item.client_name, phone: item.client_phone }
                    slots.value[slotIndex].service = services.value.find(s => s.id === item.service_id)
                    slots.value[slotIndex].is_offline_pending = true
                }
            }
        })
    }

    async function fetchSlots() {
        loading.value = true
        fetchError.value = ''
        isCachedData.value = false
        
        try {
            const dateStr = formatDateLocal(selectedDate.value)
            if (!props.user?.id) {
                console.error('User ID is missing in props')
                fetchError.value = 'User ID missing'
                return
            }
        
            const cacheKey = `slots_${props.user.id}_${dateStr}`
        
            // Попытка загрузить из кэша
            const cached = localStorage.getItem(cacheKey)
            if (cached) {
                try {
                    const parsed = JSON.parse(cached)
                    slots.value = parsed.slots || []
                    isDayOff.value = parsed.meta?.is_day_off || false
                    dayOffId.value = parsed.meta?.day_off_id || null
                    
                    if (!navigator.onLine) {
                         isCachedData.value = true
                         applyQueueToSlots()
                         loading.value = false
                         return
                    }
                } catch (e) {
                    console.error('Error parsing cached slots', e)
                }
            }
        
            try {
                const res = await apiFetch(`/api/masters/${props.user.id}/slots?date=${encodeURIComponent(dateStr)}`)
                if (!res.ok) {
                    throw new Error(`API Error: ${res.status} ${res.statusText}`)
                }
                const json = await res.json()
                const data = json.data || []
                slots.value = Array.isArray(data) ? data : []
                isDayOff.value = json.meta?.is_day_off || false
                dayOffId.value = json.meta?.day_off_id || null
                isCachedData.value = false
                
                localStorage.setItem(cacheKey, JSON.stringify({
                    slots: slots.value,
                    meta: { is_day_off: isDayOff.value, day_off_id: dayOffId.value },
                    timestamp: Date.now()
                }))
                
            } catch (e) {
                console.error('fetchSlots error:', e)
                if (cached) {
                     isCachedData.value = true
                } else {
                     fetchError.value = e.message
                }
            }
        } finally {
            loading.value = false
            applyQueueToSlots()
        }
    }

    async function makeDayOff() {
        const dateStr = formatDateLocal(selectedDate.value)
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        const res = await apiFetch('/api/master/schedule-exceptions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ type: 'day_off', date: dateStr }),
            credentials: 'same-origin',
        })
        if (!res.ok) {
            const data = await res.json().catch(() => ({}))
            alert(data.message || 'Ошибка: не удалось сделать выходным')
        }
        await fetchSlots()
    }
      
    async function cancelDayOff() {
        if (!dayOffId.value) return
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        const res = await apiFetch(`/api/master/schedule-exceptions/${dayOffId.value}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            credentials: 'same-origin',
        })
        if (!res.ok) {
            const data = await res.json().catch(() => ({}))
            alert(data.message || 'Ошибка: не удалось отменить выходной')
        }
        await fetchSlots()
    }

    onMounted(fetchSlots)
    watch(selectedDate, fetchSlots)
    watch(() => appointmentQueue.value.length, applyQueueToSlots)

    return {
        selectedDate,
        slots,
        isDayOff,
        dayOffId,
        loading,
        fetchError,
        isCachedData,
        formatDateLocal,
        fetchSlots,
        makeDayOff,
        cancelDayOff,
        apiFetch // Export helper for others
    }
}
