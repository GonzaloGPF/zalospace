<script setup>
import Translator from '@/objects/Translator.js'
import {computed} from 'vue'

const props = defineProps(['technology'])
const MAX_LEVEL = 5

const levelValue = computed(() => props.technology?.level.value)
const label = computed(() => Translator.tl(props.technology?.level.name))

const getTechClass = (value) => {
  if (value > levelValue.value) {
    return 'bg-gray-700'
  }
  const valueToBg = {
    1: 'bg-red-900',
    2: 'bg-orange-500',
    3: 'bg-yellow-500',
    4: 'bg-green-700',
    5: 'bg-green-500',
  }
  return valueToBg[levelValue.value] ?? 'bg-gray-700'
}
</script>
<template>
  <div class="flex gap-1 min-h-6">
    <div
      v-for="levelValue in MAX_LEVEL"
      :key="levelValue"
      :title="label"
      :class="getTechClass(levelValue)"
      class="w-2 h-4"
    />
  </div>
</template>