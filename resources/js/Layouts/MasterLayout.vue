<template>
  <div>
    <div v-if="trialForbidden" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
      <div class="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
        <div class="text-2xl font-semibold mb-3">Подписка требуется</div>
        <div class="text-gray-700 mb-6">Доступ к функциям ограничен. Оформите подписку, чтобы продолжить.</div>
        <button class="inline-flex items-center rounded bg-amber-600 text-white px-4 py-2" @click="goPay">Перейти к оплате</button>
      </div>
    </div>
    <slot />
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue'

const trialForbidden = ref(false)
let origFetch = null

function goPay() {
  try { window.location.href = '/pay' } catch (e) {}
}

function handleForbidden() { trialForbidden.value = true }

onMounted(() => {
  window.addEventListener('trial-forbidden', handleForbidden)
  if (!origFetch && typeof window.fetch === 'function') {
    origFetch = window.fetch.bind(window)
    window.fetch = async (...args) => {
      const res = await origFetch(...args)
      try {
        const url = String(args?.[0] || '')
        if (url.startsWith('/api/') && res?.status === 403) {
          window.dispatchEvent(new Event('trial-forbidden'))
        }
      } catch (e) {}
      return res
    }
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('trial-forbidden', handleForbidden)
})
</script>

<style scoped>
</style>
