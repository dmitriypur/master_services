import { ref, onMounted, onUnmounted, watch } from 'vue'

export function useOfflineQueue(queueKey, processItemFn) {
  const isOnline = ref(navigator.onLine)
  const queue = ref([])
  const isSyncing = ref(false)

  // Telegram WebApp helper
  function updateTgClosingConfirmation() {
    if (typeof window !== 'undefined' && window.Telegram?.WebApp) {
      if (queue.value.length > 0) {
        window.Telegram.WebApp.enableClosingConfirmation()
      } else {
        window.Telegram.WebApp.disableClosingConfirmation()
      }
    }
  }

  function updateOnlineStatus() {
    isOnline.value = navigator.onLine
    if (isOnline.value) {
      // Небольшая задержка, чтобы соединение точно установилось
      setTimeout(sync, 1000)
    }
  }

  function loadQueue() {
    try {
      const s = localStorage.getItem(queueKey)
      if (s) queue.value = JSON.parse(s)
      updateTgClosingConfirmation()
    } catch (e) {
      console.error('Error loading queue', e)
    }
  }

  function saveQueue() {
    localStorage.setItem(queueKey, JSON.stringify(queue.value))
    updateTgClosingConfirmation()
  }

  function addToQueue(item) {
    const queueItem = { ...item, _id: Date.now() + Math.random() }
    queue.value.push(queueItem)
    saveQueue()
  }

  async function sync() {
    if (queue.value.length === 0 || isSyncing.value || !isOnline.value) return

    isSyncing.value = true
    
    // Копия очереди для итерации, но модифицировать будем this.queue
    // Проходимся по очереди. Если успех - удаляем. Если ошибка - оставляем (или удаляем, если фатальная).
    // Чтобы не заблокировать очередь одной битой записью, при ошибке переходим к следующему,
    // но "битый" элемент останется в начале.
    // Улучшение: удалять 4xx ошибки (клиентские), оставлять сетевые.
    
    const items = [...queue.value]
    const remaining = []

    for (const item of items) {
      if (!navigator.onLine) {
        remaining.push(item)
        continue
      }

      try {
        await processItemFn(item)
        // Успех - элемент не добавляем в remaining
      } catch (e) {
        console.error('Sync error', e)
        // Проверяем статус ошибки, если возможно
        // Если ошибка явно клиентская (валидация), удаляем, чтобы не забивать очередь?
        // Или помечаем как "ошибка синхронизации"?
        // Пока просто оставляем, если это не явный успех.
        
        // Если e.status есть (из fetch response) и он 4xx (кроме 401, 419, 408, 429), то удаляем.
        // 401/419 - проблема авторизации, может решиться перезаходом, данные терять жалко.
        // 422 - валидация, данные некорректны, но можно было бы дать возможность исправить (сложно в фоне). Пока удаляем.
        const retryableCodes = [401, 419, 408, 429]
        if (e.status && e.status >= 400 && e.status < 500 && !retryableCodes.includes(e.status)) {
             console.warn('Removing invalid item from queue', item)
        } else {
             remaining.push(item)
        }
      }
    }
    
    queue.value = remaining
    saveQueue()
    isSyncing.value = false
  }

  onMounted(() => {
    window.addEventListener('online', updateOnlineStatus)
    window.addEventListener('offline', updateOnlineStatus)
    loadQueue()
    // Пробуем синхронизироваться при загрузке
    if (isOnline.value) {
        setTimeout(sync, 1000)
    }
  })

  onUnmounted(() => {
    window.removeEventListener('online', updateOnlineStatus)
    window.removeEventListener('offline', updateOnlineStatus)
  })

  return {
    isOnline,
    queue,
    addToQueue,
    sync,
    isSyncing
  }
}
