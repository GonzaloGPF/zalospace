<script setup>
import _ from 'lodash'
import { computed, ref } from 'vue'
import AppButton from '@/Components/core/AppButton.vue'
import AppExpandable from '@/Components/core/AppExpandable.vue'
import Translator from '@/objects/Translator.js'
import TechIcon from '@/Components/TechIcon.vue'

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

const sortedTechnologies = computed(() =>
  _.chain(props.technologies)
    .orderBy(['level.value', 'level.name'], ['desc'])
    .value()
)

const hasMore = computed(
  () => !!props.limit && props.limit < props.technologies.length
)

const visibleTechnologies = computed(() =>
  hasMore.value
    ? sortedTechnologies.value.slice(0, props.limit)
    : sortedTechnologies.value
)

const extraTechnologies = computed(() =>
  hasMore.value ? sortedTechnologies.value.slice(props.limit) : []
)
</script>
<template>
  <div v-if="technologies.length" class="@container">
    <div
      class="grid grid-cols-3 @sm:grid-cols-4 @lg:grid-cols-6 @2xl:grid-cols-8 gap-4"
    >
      <TechIcon
        v-for="technology in visibleTechnologies"
        :key="technology.name"
        :technology="technology"
      />
    </div>

    <AppExpandable :expanded="viewMore">
      <div
        class="grid grid-cols-3 @sm:grid-cols-4 @lg:grid-cols-6 @2xl:grid-cols-8 gap-4 mt-4"
      >
        <TechIcon
          v-for="technology in extraTechnologies"
          :key="technology.name"
          :technology="technology"
        />
      </div>
    </AppExpandable>

    <div v-if="hasMore" class="flex justify-center mt-4">
      <AppButton
        variant="plain"
        :label="Translator.tl(viewMore ? 'view_less' : 'view_more')"
        @click="viewMore = !viewMore"
      />
    </div>
  </div>
</template>
