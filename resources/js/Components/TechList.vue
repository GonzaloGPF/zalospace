<script setup>
import _ from 'lodash'
import { computed, ref } from 'vue'
import AppButton from '@/Components/core/AppButton.vue'
import Translator from '@/objects/Translator.js'

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
  .orderBy(['level', 'name'], ['desc'])
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
      <TransitionGroup
        name="technology"
      >
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
        <a
          v-for="technology in vTechnologies"
          :key="technology.name"
          :href="technology.url"
          :title="technology.name"
          target="_blank"
          class="block p-2 rounded-lg link flex items-center justify-center"
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
