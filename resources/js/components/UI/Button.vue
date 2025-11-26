<template>
  <button :class="classes" v-bind="$attrs"><slot /></button>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({ variant: { type: String, default: 'primary' } })
const classes = computed(() => {
  const base = 'inline-flex items-center rounded-lg px-4 py-2 text-sm transition'
  switch (props.variant) {
    case 'secondary': return base + ' bg-gray-200 text-gray-900 hover:bg-gray-300'
    case 'outline': return base + ' border hover:bg-gray-50'
    default: return base + ' bg-black text-white hover:bg-gray-900'
  }
})
</script>

<style scoped>
</style>
