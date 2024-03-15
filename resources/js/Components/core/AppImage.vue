<script setup>
import AppModal from '@/Components/core/AppModal.vue'
import { ref } from 'vue'

defineProps({
  image: String,
  title: String,
  label: String,
  modal: Boolean,
})
const show = ref(false)
</script>
<template>
  <div>
    <img
      v-if="image"
      :src="image"
      :alt="title"
      :class="['rounded-lg shadow-md bg-gray-800 p-3', { 'cursor-pointer': modal }]"
      width="150"
      @click="show = true"
    />
    <span
      v-if="label"
      v-text="label"
    />
    <app-modal
      v-if="modal"
      :show="show"
      :title="title"
      @close="show = false"
    >
      <img
        :src="image"
        :alt="title"
        class="mx-auto"
      />
    </app-modal>
  </div>
</template>