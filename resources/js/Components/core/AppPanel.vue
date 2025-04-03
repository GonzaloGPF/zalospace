<script setup>
defineProps({
  title: {
    type: String,
    default: null,
  },
  content: {
    type: String,
    default: null,
  },
  image: {
    type: String,
    default: null,
  },
})
</script>
<template>
  <div
    class="flex md:justify-start items-center flex-col md:flex-row text-gray-500 dark:text-gray-400 rounded-md p-5"
  >
    <slot name="image">
      <img
        v-if="image"
        :src="image"
        :alt="title"
        width="150"
        class="mr-5 rounded-full mb-5 md:mb-0"
      />
    </slot>
    <slot name="icon" />
    <slot>
      <div class="w-full">
        <slot name="title">
          <h1
            v-if="title"
            class="brand-font text-xl font-semibold max-w-md flex-grow mb-3 text-gray-900 dark:text-white"
            v-text="title"
          />
        </slot>
        <slot name="description">
          <p v-text="content"></p>
        </slot>
      </div>
    </slot>
  </div>
</template>
