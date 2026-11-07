<script setup>
import TechLevel from '@/Components/TechLevel.vue'

defineProps({
  technology: {
    type: Object,
    required: true,
  },
})
</script>
<template>
  <div class="flex flex-col justify-center items-center min-w-0">
    <a
      :href="technology.url"
      :title="technology.name"
      target="_blank"
      class="block p-2 rounded-lg link flex flex-col items-center justify-center mt-auto min-w-0"
    >
      <div class="flex flex-col items-center min-w-0">
        <img
          :src="technology.image"
          :alt="technology.name"
          class="mx-auto rounded-lg"
          width="50"
        />
        <span class="truncate max-w-full" v-text="technology.name" />
      </div>
    </a>
    <TechLevel :technology="technology" />
  </div>
</template>
