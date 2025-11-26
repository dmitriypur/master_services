<template>
  <div>
    <header class="w-full bg-gray-100 border-b">
      <div class="max-w-6xl mx-auto px-4 py-2 flex items-center justify-between">
        <div class="font-semibold">Сервис</div>
        <nav class="flex items-center gap-3 text-sm">
          <Link href="/master/calendar" class="text-blue-600 hover:underline">Календарь</Link>
          <Link href="/master/settings" class="text-blue-600 hover:underline">Настройки</Link>
        </nav>
        <div class="text-sm text-gray-700" v-if="user">
          <span class="font-medium">{{ user.name }}</span>
          <span class="ml-2">({{ user.role ?? 'user' }})</span>
        </div>
        <div class="text-sm text-gray-500" v-else>
          Неавторизован
        </div>
      </div>
    </header>
    <main>
      <slot />
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
</script>

<style scoped>
</style>