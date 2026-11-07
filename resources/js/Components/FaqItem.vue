<script setup>
import { ref } from 'vue'
import AppExpandable from '@/Components/core/AppExpandable.vue'

defineProps({
  question: {
    type: String,
    required: true,
  },
  answer: {
    type: String,
    required: true,
  },
})

const open = ref(false)
</script>
<template>
  <div class="border-b border-gray-300 dark:border-gray-600 py-4">
    <button
      type="button"
      class="brand-font flex items-center justify-between w-full text-left font-semibold text-gray-900 dark:text-white"
      @click="open = !open"
    >
      <span v-text="question" />
      <span
        class="ml-4 text-xl leading-none shrink-0"
        v-text="open ? '−' : '+'"
      />
    </button>
    <AppExpandable :expanded="open">
      <p class="mt-3 text-gray-500 dark:text-gray-400" v-text="answer" />
    </AppExpandable>
  </div>
</template>
