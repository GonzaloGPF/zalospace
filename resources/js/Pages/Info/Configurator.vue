<script setup>
import BaseLayout from '@/Layouts/BaseLayout.vue'
import Translator from '@/objects/Translator.js'
import AppCard from '@/Components/core/AppCard.vue'
</script>
<template>
  <BaseLayout>
    <AppCard
      :title="Translator.tl('configurator')"
      :description="Translator.t('help.configurator_description')"
      icon="work"
      class="max-w-md mx-auto"
    />
    <p
      class="mt-10 text-center"
      v-text="Translator.t('help.work_in_progress')"
    />
  </BaseLayout>
</template>
