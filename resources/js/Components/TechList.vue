<script setup>
import _ from 'lodash'
import { computed, ref } from 'vue'
import AppButton from '@/Components/core/AppButton.vue'
import Translator from '@/objects/Translator.js'
import TechLevel from '@/Components/TechLevel.vue'

const props = defineProps({
  technologies: {
    type: Array,
    default: null,
  },
  limit: {
    type: Number,
    default: 6,
  },
})
const viewMore = ref(false)
const vLimit = computed(() => {
  if (viewMore.value) {
    return props.technologies.length
  }

  return props.limit ?? props.technologies.length
})
const vTechnologies = computed(() =>
  _.chain(props.technologies)
    .orderBy(['level.value', 'level.name'], ['desc'])
    .slice(0, +vLimit.value)
    .value()
)
</script>
<template>
  <div v-if="technologies.length" class="@container">
    <div
      class="grid grid-cols-3 @sm:grid-cols-4 @lg:grid-cols-6 @2xl:grid-cols-8 gap-4"
    >
      <TransitionGroup name="technology">
        <div
          v-for="technology in vTechnologies"
          :key="technology.name"
          class="flex flex-col justify-center items-center min-w-0"
        >
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
      </TransitionGroup>
    </div>
    <div
      v-if="limit && limit < technologies.length"
      class="flex justify-center mt-4"
    >
      <AppButton
        variant="plain"
        :label="Translator.tl(viewMore ? 'view_less' : 'view_more')"
        @click="viewMore = !viewMore"
      />
    </div>
  </div>
</template>
<style>
.technology-move,
.technology-enter-active,
.technology-leave-active {
  transition: all 0.5s ease;
}

.technology-enter-from,
.technology-leave-to {
  opacity: 0;
}
</style>
