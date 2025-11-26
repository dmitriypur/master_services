<template>
  <div v-if="open" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/40" @click="$emit('close')"></div>
    <div class="relative mx-auto mt-24 w-full max-w-md px-4">
      <div class="bg-white rounded-xl shadow-lg p-5">
        <h3 v-if="$slots.title" class="text-lg font-semibold mb-4"><slot name="title" /></h3>
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
