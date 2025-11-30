<template>
  <div class="min-h-screen bg-gray-50">
    <header class="bg-white border-b">
      <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
        <a href="#" class="font-semibold">Client WebApp</a>
        <nav class="flex items-center gap-4 text-sm">
          <router-link to="/" class="hover:underline">Поиск</router-link>
        </nav>
      </div>
    </header>
    <main class="max-w-6xl mx-auto px-4 py-6">
      <router-view />
    </main>
  </div>
</template>

<script setup>
</script>

<style scoped>
</style>
