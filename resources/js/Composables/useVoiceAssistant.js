import { ref } from 'vue'

export function useVoiceAssistant(apiFetch, services, slots, selectedDate, form, fetchSlots, formatDateLocal) {
    const voiceOpen = ref(false)
    const voiceText = ref('')
    const voiceError = ref('')
    const isListening = ref(false)
    const isParsing = ref(false)
    const suggestedSlots = ref([])
    let recognition = null
    let silenceTimer = null
    const MAX_PHONE_DIGITS = 11

    function suggestFreeSlots(requestedTime) {
        suggestedSlots.value = []
        if (!slots.value.length) return
      
        const [reqH, reqM] = requestedTime.split(':').map(Number)
        const reqMinutes = reqH * 60 + reqM
      
        const candidates = slots.value.filter(s => {
            if (!s.available || s.is_past) return false
            const [h, m] = s.time.split(':').map(Number)
            const mins = h * 60 + m
            return Math.abs(mins - reqMinutes) <= 90
        })
        
        candidates.sort((a, b) => {
            const [ah, am] = a.time.split(':').map(Number)
            const [bh, bm] = b.time.split(':').map(Number)
            const diffA = Math.abs((ah * 60 + am) - reqMinutes)
            const diffB = Math.abs((bh * 60 + bm) - reqMinutes)
            return diffA - diffB
        })
      
        suggestedSlots.value = candidates.slice(0, 3)
    }

    function toggleRecording() {
        if (isListening.value) {
            stopRecording()
        } else {
            startRecording()
        }
    }

    function startRecording() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition
        if (!SpeechRecognition) {
            alert('Ваш браузер не поддерживает голосовой ввод. Попробуйте Chrome или Safari.')
            return
        }
    
        recognition = new SpeechRecognition()
        recognition.lang = 'ru-RU'
        recognition.continuous = false
        recognition.interimResults = false
    
        recognition.onstart = () => {
            isListening.value = true
            voiceError.value = ''
            if (silenceTimer) clearTimeout(silenceTimer)
        }
    
        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript
            voiceText.value = (voiceText.value ? voiceText.value.trim() + ' ' : '') + transcript
        }
    
        recognition.onerror = (event) => {
            console.error('Speech recognition error', event.error)
            if (event.error === 'not-allowed') {
                voiceError.value = 'Доступ к микрофону запрещен.'
            } else if (event.error !== 'no-speech') {
                voiceError.value = 'Ошибка распознавания: ' + event.error
            }
            stopRecording()
        }
    
        recognition.onend = () => {
            stopRecording()
            if (voiceText.value.trim().length > 0 && !isParsing.value) {
                parseVoice()
            }
        }
    
        recognition.start()
    }
    
    function stopRecording() {
        isListening.value = false
        if (silenceTimer) clearTimeout(silenceTimer)
        if (recognition) {
            recognition.stop()
            recognition = null
        }
    }

    async function parseVoice() {
        voiceError.value = ''
        const text = voiceText.value.trim()
        if (!text) { voiceError.value = 'Введите или продиктуйте текст'; return }
        
        isParsing.value = true
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            const res = await apiFetch('/api/master/parse-voice-command', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ text }),
                credentials: 'include',
            })
            
            if (!res.ok) {
                try { const d = await res.json(); voiceError.value = d.message || 'Ошибка распознавания' } catch (e) { voiceError.value = 'Ошибка распознавания' }
                return
            }
        
            const data = await res.json().catch(() => ({}))
            const r = data || {}
            
            let changed = false
            if (r.client_name) { form.value.client_name = String(r.client_name); changed = true }
            if (r.phone) { form.value.client_phone = String(r.phone).replace(/\D+/g, '').slice(0, MAX_PHONE_DIGITS); changed = true }
            
            const isGlobalMode = !form.value.time
            
            if (isGlobalMode && r.date) {
                const newDate = new Date(r.date)
                if (!isNaN(newDate) && formatDateLocal(newDate) !== form.value.date) {
                    selectedDate.value = newDate
                    form.value.date = formatDateLocal(newDate)
                    changed = true
                    await fetchSlots()
                }
            }
        
            if (isGlobalMode && r.time) {
               const t = String(r.time)
               const m = t.match(/(\d{1,2}:\d{2})/)
               if (m) { 
                 const parsedTime = m[1]
                 const slot = slots.value.find(s => s.time === parsedTime)
                 
                 if (slot) {
                    if (slot.available) {
                       form.value.time = parsedTime
                       changed = true
                    } else {
                       voiceError.value = `Время ${parsedTime} занято.`
                       suggestFreeSlots(parsedTime)
                    }
                 } else {
                     voiceError.value = `Время ${parsedTime} не найдено.`
                     suggestFreeSlots(parsedTime)
                 }
               }
            }
        
            if (r.service_name && Array.isArray(services.value)) {
                const name = String(r.service_name).toLowerCase().trim()
                const found = services.value.find((s) => String(s.name || '').toLowerCase().includes(name))
                if (found) { form.value.service_id = found.id; changed = true }
            }
            
            if (!changed) {
                voiceError.value = 'Не удалось найти данные (имя, телефон или время) в тексте.'
            }
        } catch (e) {
            console.error(e)
            voiceError.value = 'Ошибка сети или сервера'
        } finally {
            isParsing.value = false
        }
    }

    return {
        voiceOpen,
        voiceText,
        voiceError,
        isListening,
        isParsing,
        suggestedSlots,
        toggleRecording,
        parseVoice,
        suggestFreeSlots
    }
}
