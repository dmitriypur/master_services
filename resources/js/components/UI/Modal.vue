<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>
    <div class="relative w-full max-w-md bg-white rounded-xl shadow-xl max-h-[90vh] flex flex-col overflow-hidden">
      <div v-if="$slots.title" class="p-4 border-b border-gray-100 bg-gray-50/50 shrink-0">
        <h3 class="text-lg font-semibold text-gray-900 leading-6"><slot name="title" /></h3>
      </div>
      <div class="p-4 overflow-y-auto overscroll-contain">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({ open: { type: Boolean, default: false } })
</script>

<style scoped>
</style>
