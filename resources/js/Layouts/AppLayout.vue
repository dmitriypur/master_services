<template>
  <div class="min-h-screen bg-gradient-to-b from-white to-gray-50">
    <main class="max-w-md sm:max-w-lg mx-auto px-3 sm:px-4 py-4 sm:py-6">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
</script>

<style scoped>
</style>
