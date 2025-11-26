<template>
  <div class="max-w-md mx-auto mt-10 p-6 bg-white border rounded">
    <h1 class="text-xl font-semibold mb-4">Вход</h1>
    <form method="POST" action="/login" class="space-y-4">
      <input type="hidden" name="_token" :value="csrf" />
      <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input name="email" type="email" required class="w-full border rounded px-3 py-2" :value="old.email || ''" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Пароль</label>
        <input name="password" type="password" required class="w-full border rounded px-3 py-2" />
      </div>
      <div class="flex items-center gap-2">
        <input id="remember" name="remember" type="checkbox" class="border rounded" />
        <label for="remember" class="text-sm">Запомнить меня</label>
      </div>
      <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
      <button type="submit" class="w-full bg-black text-white py-2 rounded">Войти</button>
    </form>
  </div>
  
</template>

<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const csrf = computed(() => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '')
const old = computed(() => page.props?.ziggy?.old ?? page.props?.old ?? {})
const error = computed(() => {
  const e = page.props?.errors ?? {}
  return e.email || e.password || ''
})
</script>

<style scoped>
</style>