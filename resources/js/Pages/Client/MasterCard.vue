<template>
  <div class="rounded border p-4 flex flex-col gap-2">
    <div class="font-semibold">{{ master.name }}</div>
    <div class="text-sm text-gray-700">{{ master.city?.name ?? '—' }}</div>
    <div class="text-sm text-gray-700">{{ master.address ?? '—' }}</div>
    <div class="text-sm text-gray-700">Услуги: {{ (master.services || []).map(s => s.name).join(', ') }}</div>
    <div class="flex items-center justify-end">
      <button class="inline-flex items-center rounded bg-indigo-700 text-white px-3 py-1.5" @click="$emit('book')">Записаться</button>
    </div>
  </div>
</template>

<script setup>
defineProps({ master: { type: Object, required: true } })
</script>

<style scoped>
</style>
