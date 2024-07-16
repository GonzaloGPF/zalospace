<script setup>
import _ from 'lodash'
import { computed, ref } from 'vue'
import AppButton from '@/Components/core/AppButton.vue'
import Translator from '@/objects/Translator.js'
import TechLevel from '@/Components/TechLevel.vue'

const props = defineProps({
  technologies: {
    type: Array,
    default: null
  },
  limit: {
    type: Number,
    default: 6
  }
})
const viewMore = ref(false)
const vLimit = computed(() => {
  if (viewMore.value) {
    return props.technologies.length
  }

  return props.limit ?? props.technologies.length
})
const vTechnologies = computed(() => _
  .chain(props.technologies)
  .orderBy(['level.value', 'level.name'], ['desc'])
  .slice(0, +vLimit.value)
  .value()
)

</script>
<template>
  <div>
    <div
      v-if="technologies.length"
      class="grid grid-cols-4 md:grid-cols-8 gap-4"
    >
      <TransitionGroup name="technology">
        <div
          v-if="limit && limit < technologies.length"
          key="button"
          class="flex items-center max-w-xs"
        >
          <app-button
            variant="plain"
            :label="Translator.tl(viewMore ? 'view_less' : 'view_more')"
            @click="viewMore = !viewMore"
          />
        </div>
        <div
          v-for="technology in vTechnologies"
          :key="technology.name"
          class="flex flex-col justify-center items-center"
        >
          <a
            :href="technology.url"
            :title="technology.name"
            target="_blank"
            class="block p-2 rounded-lg link flex flex-col items-center justify-center mt-auto"
          >
            <div class="flex flex-col items-center">
              <img
                :src="technology.image"
                :alt="technology.name"
                class="mx-auto rounded-lg"
                width="50"
              >
              <span v-text="technology.name" />
            </div>
          </a>
          <TechLevel :technology="technology" />
        </div>

      </TransitionGroup>
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
