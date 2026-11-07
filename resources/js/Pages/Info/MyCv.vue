<script setup>
import { computed } from 'vue'
import BaseLayout from '@/Layouts/BaseLayout.vue'
import Translator from '@/objects/Translator.js'

const props = defineProps({
  cv: {
    type: Object,
    required: true,
  },
})

const content = computed(() => props.cv[Translator.getLocale()] ?? props.cv.en)
const pdfUrl = computed(() =>
  route('info.cv_pdf', { locale: Translator.getLocale() })
)
</script>
<template>
  <BaseLayout
    :title="Translator.tl('my_cv')"
    :description="Translator.t('help.my_cv_description')"
    icon="user"
  >
    <div class="flex justify-center mb-10">
      <a
        :href="pdfUrl"
        class="brand-font inline-flex items-center space-x-3 px-4 py-2 rounded border shadow-sm font-semibold text-xs uppercase tracking-widest bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
      >
        {{ Translator.tl('download_cv') }}
      </a>
    </div>

    <div
      class="max-w-3xl mx-auto bg-white dark:bg-gray-800/50 rounded-lg shadow-2xl shadow-gray-500/20 dark:shadow-none dark:ring-1 dark:ring-inset dark:ring-white/5 px-6 py-8 md:px-10 md:py-10"
      v-html="content"
    />
  </BaseLayout>
</template>
