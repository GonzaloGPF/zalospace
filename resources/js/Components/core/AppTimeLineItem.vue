<script setup>
import TechList from '@/Components/TechList.vue';

defineProps({
  title: {
    type: String,
    default: null
  },
  subtitle: {
    type: String,
    default: null
  },
  url: {
    type: String,
    default: null
  },
  urlLabel: {
    type: String,
    default: null
  },
  image: {
    type: String,
    default: null
  },
  paragraphs: {
    type: Array,
    default: () => []
  },
  technologies: {
    type: Array,
    default: () => []
  },
});
</script>
<template>
    <div class="flex flex-col">
        <div>
            <div v-if="title || subtitle" class="flex flex-col justify-center">
                <span
                 class="font-semibold text-gray-900 dark:text-white"
                 v-text="title"
                />
                <span
                 v-if="subtitle"
                 class="block mt-1 mb-3 font-thin whitespace-nowrap"
                 v-text="subtitle"
                />
            </div>
        </div>
        <div class="flex flex-col">
            <div class="flex flex-col items-center md:items-start rounded-lg text-center">
                <a
                 v-if="url || image"
                 :href="url"
                 :title="urlLabel"
                 class="link mb-5"
                 target="_blank"
                >
                    <img
                     v-if="image"
                     :src="image"
                     :alt="title"
                     width="150"
                     class="rounded-lg shadow-md bg-gray-800 p-3"
                    />
                    <span v-if="urlLabel" v-text="urlLabel" />
                </a>
            </div>
            <tech-list :technologies="technologies" />
        </div>
        <p
            v-for="paragraph in paragraphs"
            class="text-gray-500 dark:text-gray-400 py-1"
            v-html="paragraph"
        />
    </div>
</template>
