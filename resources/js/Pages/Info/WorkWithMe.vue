<script setup>
import BaseLayout from '@/Layouts/BaseLayout.vue'
import Translator from '@/objects/Translator.js'
import AppCard from '@/Components/core/AppCard.vue'
import AppButton from '@/Components/core/AppButton.vue'
</script>
<template>
  <BaseLayout
    :title="Translator.tl('work_with_me')"
    :description="Translator.t('help.work_with_me_description')"
    icon="work"
  >
    <p
      class="max-w-2xl mx-auto text-center text-gray-500 dark:text-gray-400"
      v-text="Translator.t('help.work_with_me_intro')"
    />

    <AppCard
      :title="Translator.tl('services')"
      class="max-w-2xl mx-auto mt-10"
      shine
    >
      <template #description>
        <ul class="list-disc list-inside mt-3 space-y-1">
          <li v-text="Translator.t('help.work_with_me_service_1')" />
          <li v-text="Translator.t('help.work_with_me_service_2')" />
          <li v-text="Translator.t('help.work_with_me_service_3')" />
          <li v-text="Translator.t('help.work_with_me_service_4')" />
        </ul>
      </template>
    </AppCard>

    <p
      class="max-w-2xl mx-auto mt-10 text-center text-gray-500 dark:text-gray-400"
      v-text="Translator.t('help.work_with_me_availability')"
    />

    <div class="flex justify-center mt-10">
      <AppButton
        :to="route('contacts.create')"
        :label="Translator.actionTitle('contact')"
        icon="email"
      />
    </div>
  </BaseLayout>
</template>
