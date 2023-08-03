<script setup>
import AppTimeLineItem from '@/Components/core/AppTimeLineItem.vue';

defineProps({
    items: {
        type: Array,
        default: null
    }
});
</script>
<template>
  <ol class="flex flex-col space-y-8 list-disc">
    <li
      v-for="item in items"
      class="pt-2 pb-6 mt-2 border-b border-slate-300 dark:border-slate-600"
    >
      <app-time-line-item
        :title="item.title"
        :subtitle="item.subtitle"
        :url="item.url"
        :url-label="item.urlLabel"
        :image="item.image"
        :paragraphs="item.paragraphs"
        :technologies="item.technologies"
      />
    </li>
  </ol>
</template>
