<script setup>
defineProps(['percentage', 'label'])
</script>
<template>
  <div
    class="dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent rounded-lg w-full"
  >
    <div
      class="bg-green-700 rounded-lg font-bold px-1 text-white text-end"
      :style="{ width: `${percentage}%` }"
      v-text="label ?? `${percentage}%`"
    />
  </div>
</template>
