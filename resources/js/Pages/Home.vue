<template>
  <div class="min-h-screen bg-gradient-to-b from-white to-gray-50">
    <div class="max-w-md sm:max-w-lg mx-auto px-3 sm:px-4">
      <div class="py-8 sm:py-12">
        <h1 class="text-3xl font-bold tracking-tight">Сервис записи</h1>
        <p class="text-gray-600 mt-2">SPA на Vue + Inertia без перезагрузок.</p>
        <div class="mt-6 grid grid-cols-2 gap-3">
          <Link href="/master/calendar" class="inline-flex items-center justify-center rounded-lg bg-black text-white px-4 py-3">Календарь</Link>
          <Link href="/master/settings" class="inline-flex items-center justify-center rounded-lg bg-gray-900 text-white px-4 py-3">Настройки</Link>
          <Link href="/book" class="inline-flex items-center justify-center rounded-lg bg-gray-200 text-gray-900 px-4 py-3">Запись клиента</Link>
          <Link href="/login" class="inline-flex items-center justify-center rounded-lg border px-4 py-3">Вход</Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<style scoped>
</style>
